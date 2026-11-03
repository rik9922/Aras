<?php

namespace App\Services;

use App\Models\AssignAmbassador;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/** Sends one daily manager digest for recorded late/off-site check-ins. */
class CheckInDiscrepancyDigestService
{
    public function __construct(private readonly NotificationDispatchService $notifications) {}

    public function sendDailyDigest(?Carbon $day = null): int
    {
        $day ??= now();
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();
        $grace = (int) config('attendance.late_checkin_grace_minutes', 10);

        $rows = AssignAmbassador::query()
            ->with(['event', 'ambassador'])
            ->where('status', '1')
            ->whereNotNull('event_date_time')
            ->whereBetween('event_date_time', [$start, $end])
            ->whereHas('event', fn ($q) => $q->whereNotNull('start_time'))
            ->get()
            ->filter(function (AssignAmbassador $assignment) use ($grace): bool {
                $event = $assignment->event;
                if (! $event) return false;
                $timezone = app(EventTimezoneService::class)->normalizeIana($event->timezone);
                $checkIn = Carbon::parse($assignment->event_date_time, $timezone);
                $scheduled = Carbon::parse($event->start_time, $timezone)->addMinutes($grace);
                return $checkIn->greaterThanOrEqualTo($scheduled) || $assignment->is_at_event_location === false;
            });

        $byManager = $rows->filter(fn ($row) => (int) ($row->event?->manager_id ?? 0) > 0)->groupBy(fn ($row) => (int) $row->event->manager_id);
        $sent = 0;
        foreach ($byManager as $managerId => $items) {
            $alreadySent = Schema::hasTable('notifications') && Notification::query()->where('receiver_id', $managerId)
                ->where('type', 'check_in_discrepancy_digest')->whereDate('created_at', $day->toDateString())->exists();
            if ($alreadySent) continue;

            $lines = $items->map(function (AssignAmbassador $row) use ($grace): string {
                $event = $row->event;
                $tz = app(EventTimezoneService::class)->normalizeIana($event->timezone);
                $late = Carbon::parse($row->event_date_time, $tz)->greaterThanOrEqualTo(Carbon::parse($event->start_time, $tz)->addMinutes($grace));
                $flags = [];
                if ($late) $flags[] = 'late';
                if ($row->is_at_event_location === false) $flags[] = 'wrong location';
                return sprintf('#%d %s — %s (%s)', $event->id, $event->event_name ?: 'Event', $row->ambassador?->name ?: 'Ambassador', implode(', ', $flags));
            })->values()->all();
            $message = 'Check-in discrepancies requiring review: '.count($lines).'. See the daily review notification for event details.';
            $emailHtml = '<p>'.e($message).'</p><ul>'.collect($lines)->map(fn ($line) => '<li>'.e($line).'</li>')->implode('').'</ul>';
            try {
                $this->notifications->dispatch(null, (int) $managerId, 'check_in_discrepancy_digest', 'Daily check-in discrepancy review', $message, $emailHtml);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Check-in discrepancy digest failed', ['manager_id' => $managerId, 'error' => $e->getMessage()]);
            }
        }
        return $sent;
    }
}
