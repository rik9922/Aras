<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventBillingParty;
use App\Models\InvoiceMonthNotificationMark;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class InvoiceMonthMilestoneNotificationService
{
    public const TYPE_ALL_RECAPS_COMPLETE = 'invoice_month_all_recaps_complete';
    public const TYPE_NO_UPCOMING_EVENTS = 'invoice_month_no_upcoming_events';

    public function __construct(private readonly NotificationDispatchService $notifications)
    {
    }

    /**
     * @return array{ok:bool,month:string,clients_evaluated:int,all_recaps_notified:int,no_upcoming_notified:int,skipped:int,errors:int}
     */
    public function run(?string $monthInput = null, bool $dryRun = false): array
    {
        $now = now();
        $month = $this->parseMonth($monthInput, $now);
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();
        $monthKey = $monthStart->format('Y-m');

        $clientIds = $this->clientIdsWithEventsInMonth($monthStart, $monthEnd);
        if ($clientIds->isEmpty()) {
            return [
                'ok' => true,
                'month' => $monthKey,
                'clients_evaluated' => 0,
                'all_recaps_notified' => 0,
                'no_upcoming_notified' => 0,
                'skipped' => 0,
                'errors' => 0,
            ];
        }

        $recipients = User::query()->whereIn('role_id', [1, 6])->pluck('id')->filter()->values();

        $clientsEvaluated = 0;
        $allRecapsNotified = 0;
        $noUpcomingNotified = 0;
        $skipped = 0;
        $errors = 0;

        $clients = User::query()->whereIn('id', $clientIds)->get();
        foreach ($clients as $client) {
            $clientsEvaluated++;

            try {
                $allRecapsComplete = $this->allRecapsCompleteSoFar($client, $monthStart, $monthEnd, $now);
                $noUpcoming = $this->noUpcomingEventsRemain($client, $monthStart, $monthEnd, $now);

                if ($allRecapsComplete) {
                    $did = $this->maybeNotify($client, $monthKey, self::TYPE_ALL_RECAPS_COMPLETE, $recipients, $dryRun, [
                        'month_label' => $monthStart->format('F Y'),
                    ]);
                    $allRecapsNotified += $did ? 1 : 0;
                    $skipped += $did ? 0 : 1;
                }

                if ($noUpcoming) {
                    $did = $this->maybeNotify($client, $monthKey, self::TYPE_NO_UPCOMING_EVENTS, $recipients, $dryRun, [
                        'month_label' => $monthStart->format('F Y'),
                    ]);
                    $noUpcomingNotified += $did ? 1 : 0;
                    $skipped += $did ? 0 : 1;
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::warning('Invoice milestone notification evaluation failed', [
                    'client_id' => $client->id,
                    'month' => $monthKey,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'ok' => $errors === 0,
            'month' => $monthKey,
            'clients_evaluated' => $clientsEvaluated,
            'all_recaps_notified' => $allRecapsNotified,
            'no_upcoming_notified' => $noUpcomingNotified,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    private function parseMonth(?string $monthInput, Carbon $fallbackNow): Carbon
    {
        $monthInput = $monthInput ?: $fallbackNow->format('Y-m');
        try {
            return Carbon::createFromFormat('Y-m', $monthInput);
        } catch (\Throwable) {
            return $fallbackNow->copy();
        }
    }

    private function clientIdsWithEventsInMonth(Carbon $start, Carbon $end)
    {
        $primaryIds = Event::query()
            ->whereBetween('start_time', [$start, $end])
            ->whereNotNull('client_id')
            ->pluck('client_id');

        $billingIds = EventBillingParty::query()
            ->whereHas('event', function ($q) use ($start, $end) {
                $q->whereBetween('start_time', [$start, $end]);
            })
            ->pluck('client_id');

        return $primaryIds->merge($billingIds)->unique()->filter()->values();
    }

    /**
     * "All recaps are complete" is evaluated for all completed events in the month whose end_time has passed.
     * We consider an event "recapped" if:
     * - it has a recap row, OR
     * - the event was auto-zeroed (recap_status = auto_zeroed).
     */
    private function allRecapsCompleteSoFar(User $client, Carbon $start, Carbon $end, Carbon $now): bool
    {
        $base = Event::query()
            ->whereBetween('start_time', [$start, $end])
            ->where('event_status', '1')
            ->where('recap_needed', 1)
            ->whereNotNull('end_time')
            ->where('end_time', '<=', $now)
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                    ->orWhereHas('billingParties', function ($bq) use ($client) {
                        $bq->where('client_id', $client->id);
                    });
            });

        $missing = (clone $base)
            ->where(function ($q) {
                $q->whereNull('recap_status')
                    ->orWhere('recap_status', '!=', 'auto_zeroed');
            })
            ->whereDoesntHave('recap')
            ->count();

        return $missing === 0;
    }

    /**
     * "No upcoming events remain" means there are no non-cancelled events in the month whose start_time is in the future.
     */
    private function noUpcomingEventsRemain(User $client, Carbon $start, Carbon $end, Carbon $now): bool
    {
        $upcoming = Event::query()
            ->whereBetween('start_time', [$start, $end])
            ->where('start_time', '>', $now)
            ->where('event_status', '!=', '2')
            ->where(function ($q) use ($client) {
                $q->where('client_id', $client->id)
                    ->orWhereHas('billingParties', function ($bq) use ($client) {
                        $bq->where('client_id', $client->id);
                    });
            })
            ->count();

        return $upcoming === 0;
    }

    private function maybeNotify(
        User $client,
        string $monthKey,
        string $type,
        $recipientIds,
        bool $dryRun,
        array $meta = []
    ): bool {
        $monthLabel = (string) ($meta['month_label'] ?? $monthKey);

        $alreadySent = false;
        if (Schema::hasTable('invoice_month_notification_marks')) {
            $alreadySent = InvoiceMonthNotificationMark::query()
                ->where('client_id', $client->id)
                ->where('month', $monthKey)
                ->where('type', $type)
                ->exists();
        }

        if ($alreadySent) {
            return false;
        }

        if (! $dryRun && Schema::hasTable('invoice_month_notification_marks')) {
            InvoiceMonthNotificationMark::create([
                'client_id' => $client->id,
                'month' => $monthKey,
                'type' => $type,
                'triggered_at' => now(),
                'meta' => $meta,
            ]);
        }

        $title = $type === self::TYPE_ALL_RECAPS_COMPLETE
            ? "All recaps complete: {$client->name} ({$monthLabel})"
            : "No upcoming events: {$client->name} ({$monthLabel})";

        $message = $type === self::TYPE_ALL_RECAPS_COMPLETE
            ? "All required recaps have been submitted for {$client->name} for {$monthLabel} (so far)."
            : "There are no upcoming events remaining for {$client->name} for {$monthLabel}.";

        if ($dryRun) {
            return true;
        }

        foreach ($recipientIds as $receiverId) {
            $this->notifications->dispatch(
                senderId: null,
                receiverId: (int) $receiverId,
                type: $type,
                title: $title,
                message: $message,
                emailContentHtml: null,
                bookingId: null
            );
        }

        return true;
    }
}

