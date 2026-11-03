<?php

namespace App\Services;

use App\Mail\ClientBudgetAlertMail;
use App\Models\Brand;
use App\Models\Event;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class ClientBudgetService
{
    /**
     * Event statuses that consume program allocation/budget.
     *
     * Pending (0) + Completed (1) are both tracked; Cancelled (2) is excluded.
     *
     * @var array<int, string>
     */
    protected array $trackedStatuses = ['0', '1'];

    public function billableTotal(?float $labor, ?float $buyback, ?float $cups): float
    {
        return round(
            (float) ($labor ?? 0) + (float) ($buyback ?? 0) + (float) ($cups ?? 0),
            2
        );
    }

    public function tracksBudget(?User $client): bool
    {
        if (! $client || (int) $client->role_id !== 3) {
            return false;
        }

        $maxAmt = $client->budget_max_amount;
        $maxEv = $client->budget_max_events;
        $hasAmount = $maxAmt !== null && (float) $maxAmt > 0;
        $hasEvents = $maxEv !== null && (int) $maxEv > 0;

        return $hasAmount || $hasEvents;
    }

    /**
     * Usage for tracked events (Pending + Completed) for this Bill To.
     *
     * @return array{amount_used: float, events_used: int}
     */
    public function usageStats(int $clientId, ?int $excludeEventId = null): array
    {
        $q = Event::query()
            ->where('client_id', $clientId)
            ->whereIn('event_status', $this->trackedStatuses);

        if ($excludeEventId) {
            $q->where('id', '!=', $excludeEventId);
        }

        $events = $q->get(['id', 'labor_amount', 'buyback_amount', 'cups_amount']);

        $total = 0.0;
        foreach ($events as $e) {
            $total += $this->billableTotal(
                $e->labor_amount !== null ? (float) $e->labor_amount : null,
                $e->buyback_amount !== null ? (float) $e->buyback_amount : null,
                $e->cups_amount !== null ? (float) $e->cups_amount : null,
            );
        }

        return [
            'amount_used' => round($total, 2),
            'events_used' => $events->count(),
        ];
    }

    /**
     * @return array<int, string>
     */
    public function recipientEmails(User $client, ?Brand $brand = null): array
    {
        $emails = config('budget.management_emails', []);
        if (! is_array($emails)) {
            $emails = [];
        }

        foreach ([$client->budget_alert_email ?? null, $brand?->budget_alert_email ?? null] as $e) {
            if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $e;
            }
        }

        return array_values(array_unique(array_filter($emails)));
    }

    /**
     * Whether this event may be marked Completed (event_status = 1) from a budget perspective.
     *
     * @return array{
     *     allowed: bool,
     *     pending: bool,
     *     message: string|null,
     * }
     */
    public function evaluateMarkCompleted(Event $event, User $client): array
    {
        if ($event->budget_approval_status === 'approved') {
            return ['allowed' => true, 'pending' => false, 'message' => null];
        }

        if (! $this->tracksBudget($client)) {
            return ['allowed' => true, 'pending' => false, 'message' => null];
        }

        $billable = $this->billableTotal(
            $event->labor_amount !== null ? (float) $event->labor_amount : null,
            $event->buyback_amount !== null ? (float) $event->buyback_amount : null,
            $event->cups_amount !== null ? (float) $event->cups_amount : null,
        );

        $projection = $this->evaluateProjection($client, $billable, $event->id);
        $amountExceeded = $projection['amount_exceeded'];
        $eventsExceeded = $projection['events_exceeded'];
        $projectedAmount = $projection['projected_amount'];
        $projectedEvents = $projection['projected_events'];
        $maxAmount = $projection['max_amount'];
        $maxEvents = $projection['max_events'];

        if ($event->budget_approval_status === 'denied' && ($amountExceeded || $eventsExceeded)) {
            return [
                'allowed' => false,
                'pending' => false,
                'message' => 'Budget approval was denied for this event. Update billable amounts or the client program limits, then try again.',
            ];
        }

        if (! $amountExceeded && ! $eventsExceeded) {
            return ['allowed' => true, 'pending' => false, 'message' => null];
        }

        $parts = [];
        if ($amountExceeded) {
            $parts[] = sprintf(
                'Billable total would be $%s vs max budget $%s.',
                number_format($projectedAmount, 2),
                number_format($maxAmount, 2)
            );
        }
        if ($eventsExceeded) {
            $parts[] = sprintf(
                'Completed events would be %d vs max allocations %d.',
                $projectedEvents,
                $maxEvents
            );
        }
        $detail = implode(' ', $parts);

        if (! $client->budget_enforce) {
            return ['allowed' => true, 'pending' => false, 'message' => null];
        }

        if ($client->budget_require_approval) {
            return [
                'allowed' => false,
                'pending' => true,
                'message' => 'Budget / allocation limit would be exceeded. Management will be notified to approve this completion.',
            ];
        }

        return [
            'allowed' => false,
            'pending' => false,
            'message' => 'Cannot mark Completed: '.$detail,
        ];
    }

    /**
     * Apply pending budget approval state and notify management.
     */
    public function requestBudgetApproval(Event $event, User $client): void
    {
        if ($event->budget_approval_status === 'pending') {
            return;
        }

        $event->budget_approval_status = 'pending';
        $event->budget_approval_requested_at = now();
        $event->budget_approval_resolved_by = null;
        $event->budget_approval_resolved_at = null;
        $event->budget_approval_note = null;
        $event->save();

        $brand = $event->brand_id ? Brand::find($event->brand_id) : null;
        $to = $this->recipientEmails($client, $brand);

        if ($to === []) {
            return;
        }

        $program = $client->budget_program_name ? ' ('.$client->budget_program_name.')' : '';
        $lines = [
            'A user attempted to mark an event Completed for Bill To '.$client->name.$program.', which would exceed configured budget or allocation limits.',
            'Event #'.$event->id.($event->event_name ? ' — '.$event->event_name : ''),
            'Please review the event in the admin panel and approve or deny the budget exception.',
        ];

        $url = route('admin.show-event', ['id' => $event->id], true);

        Mail::to($to)->send(new ClientBudgetAlertMail(
            subjectLine: 'Budget approval needed — Event #'.$event->id,
            headline: 'Budget / allocation approval required',
            lines: $lines,
            actionLabel: 'View event',
            actionUrl: $url,
        ));
    }

    public function notifyThresholdsAfterCompletion(Event $event, User $client): void
    {
        if (! $this->tracksBudget($client)) {
            return;
        }

        $brand = $event->brand_id ? Brand::find($event->brand_id) : null;
        $to = $this->recipientEmails($client, $brand);

        if ($to === []) {
            return;
        }

        $after = $this->usageStats($client->id);
        $before = $this->usageStats($client->id, $event->id);

        $maxAmount = $client->budget_max_amount !== null && (float) $client->budget_max_amount > 0
            ? (float) $client->budget_max_amount
            : null;
        $maxEvents = $client->budget_max_events !== null && (int) $client->budget_max_events > 0
            ? (int) $client->budget_max_events
            : null;

        $warnPct = max(1, min(100, (int) ($client->budget_warn_percent ?? 80)));
        $warnAmountThreshold = $maxAmount !== null ? round($maxAmount * $warnPct / 100, 2) : null;
        $warnEventsThreshold = $maxEvents !== null ? (int) max(1, ceil($maxEvents * $warnPct / 100)) : null;

        $lines = [];
        $subjectParts = [];

        if ($maxAmount !== null) {
            if ($after['amount_used'] >= $maxAmount && $before['amount_used'] < $maxAmount) {
                $subjectParts[] = $after['amount_used'] > $maxAmount ? 'Budget exceeded' : 'Budget reached';
                $lines[] = sprintf(
                    'Bill To %s is now at or over the dollar budget: $%s used vs $%s max (pending + completed events; labor + buyback + cups).',
                    $client->name,
                    number_format($after['amount_used'], 2),
                    number_format($maxAmount, 2)
                );
            } elseif (
                $warnAmountThreshold !== null
                && $after['amount_used'] >= $warnAmountThreshold
                && $before['amount_used'] < $warnAmountThreshold
            ) {
                if (Cache::add('budget:warn:amount:'.$client->id, 1, now()->addDay())) {
                    $subjectParts[] = 'Budget approaching limit';
                    $lines[] = sprintf(
                        'Bill To %s has reached $%s of $%s budget (~%d%% warning threshold).',
                        $client->name,
                        number_format($after['amount_used'], 2),
                        number_format($maxAmount, 2),
                        $warnPct
                    );
                }
            }
        }

        if ($maxEvents !== null) {
            if ($after['events_used'] >= $maxEvents && $before['events_used'] < $maxEvents) {
                $subjectParts[] = $after['events_used'] > $maxEvents ? 'Allocations exceeded' : 'Allocations reached';
                $lines[] = sprintf(
                    'Bill To %s now has %d tracked events (pending + completed, max %d).',
                    $client->name,
                    $after['events_used'],
                    $maxEvents
                );
            } elseif (
                $warnEventsThreshold !== null
                && $after['events_used'] >= $warnEventsThreshold
                && $before['events_used'] < $warnEventsThreshold
            ) {
                if (Cache::add('budget:warn:events:'.$client->id, 1, now()->addDay())) {
                    $subjectParts[] = 'Allocations approaching limit';
                    $lines[] = sprintf(
                        'Bill To %s has %d tracked events of %d allowed (~%d%% warning threshold).',
                        $client->name,
                        $after['events_used'],
                        $maxEvents,
                        $warnPct
                    );
                }
            }
        }

        if ($lines === []) {
            return;
        }

        $program = $client->budget_program_name ? ' — '.$client->budget_program_name : '';
        Mail::to($to)->send(new ClientBudgetAlertMail(
            subjectLine: implode(' · ', $subjectParts).' — '.$client->name.$program,
            headline: 'Budget / allocation notification',
            lines: $lines,
            actionLabel: null,
            actionUrl: null,
        ));
    }

    /**
     * Validate billable change on an already-completed event.
     *
     * @return string|null Error message or null if OK
     */
    public function validateBillableChangeForCompletedEvent(Event $event, User $client, float $labor, float $buyback, float $cups): ?string
    {
        if ($event->budget_approval_status === 'approved') {
            return null;
        }

        if (! $this->tracksBudget($client) || ! $client->budget_enforce) {
            return null;
        }

        $newBillable = $this->billableTotal($labor, $buyback, $cups);
        $projection = $this->evaluateProjection($client, $newBillable, $event->id);
        $amountExceeded = $projection['amount_exceeded'];
        $eventsExceeded = $projection['events_exceeded'];

        if (! $amountExceeded && ! $eventsExceeded) {
            return null;
        }

        if ($client->budget_require_approval) {
            return 'Updated billable amounts would exceed this Bill To\'s budget or allocations. Reduce amounts, adjust the client budget, or request a budget approval on the event before increasing totals.';
        }

        return 'Updated billable amounts would exceed this Bill To\'s budget or allocations. Reduce amounts or adjust the client budget settings.';
    }

    /**
     * Validate a new or existing tracked event against program limits.
     *
     * @return array{allowed: bool, pending: bool, message: string|null}
     */
    public function evaluateTrackedEvent(User $client, float $labor, float $buyback, float $cups, ?int $excludeEventId = null): array
    {
        if (! $this->tracksBudget($client)) {
            return ['allowed' => true, 'pending' => false, 'message' => null];
        }

        $billable = $this->billableTotal($labor, $buyback, $cups);
        $projection = $this->evaluateProjection($client, $billable, $excludeEventId);

        if (! $projection['amount_exceeded'] && ! $projection['events_exceeded']) {
            return ['allowed' => true, 'pending' => false, 'message' => null];
        }

        $parts = [];
        if ($projection['amount_exceeded']) {
            $parts[] = sprintf(
                'Tracked billable total would be $%s vs max budget $%s.',
                number_format($projection['projected_amount'], 2),
                number_format((float) $projection['max_amount'], 2)
            );
        }
        if ($projection['events_exceeded']) {
            $parts[] = sprintf(
                'Tracked events would be %d vs max allocations %d.',
                $projection['projected_events'],
                (int) $projection['max_events']
            );
        }
        $detail = implode(' ', $parts);

        if (! $client->budget_enforce) {
            return ['allowed' => true, 'pending' => false, 'message' => null];
        }

        if ($client->budget_require_approval) {
            return [
                'allowed' => false,
                'pending' => true,
                'message' => 'Budget / allocation limit would be exceeded for this Bill To. Management approval is required before finalizing this over-limit booking.',
            ];
        }

        return [
            'allowed' => false,
            'pending' => false,
            'message' => 'Cannot save event: '.$detail,
        ];
    }

    /**
     * @return array{
     *   projected_amount: float,
     *   projected_events: int,
     *   max_amount: float|null,
     *   max_events: int|null,
     *   amount_exceeded: bool,
     *   events_exceeded: bool
     * }
     */
    protected function evaluateProjection(User $client, float $billable, ?int $excludeEventId = null): array
    {
        $stats = $this->usageStats($client->id, $excludeEventId);
        $projectedAmount = round($stats['amount_used'] + $billable, 2);
        $projectedEvents = $stats['events_used'] + 1;

        $maxAmount = $client->budget_max_amount !== null && (float) $client->budget_max_amount > 0
            ? (float) $client->budget_max_amount
            : null;
        $maxEvents = $client->budget_max_events !== null && (int) $client->budget_max_events > 0
            ? (int) $client->budget_max_events
            : null;

        return [
            'projected_amount' => $projectedAmount,
            'projected_events' => $projectedEvents,
            'max_amount' => $maxAmount,
            'max_events' => $maxEvents,
            'amount_exceeded' => $maxAmount !== null && $projectedAmount > $maxAmount,
            'events_exceeded' => $maxEvents !== null && $projectedEvents > $maxEvents,
        ];
    }
}
