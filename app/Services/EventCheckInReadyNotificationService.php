<?php

namespace App\Services;

use App\Models\AssignAmbassador;
use Illuminate\Support\Facades\Log;

class EventCheckInReadyNotificationService
{
    public function __construct(
        private readonly NotificationDispatchService $notificationDispatch,
        private readonly EventCheckInService $eventCheckInService
    ) {}

    /**
     * Notify assigned ambassadors when their event start time is reached.
     */
    public function processDueAssignments(): int
    {
        $sentCount = 0;

        $assignments = AssignAmbassador::query()
            ->with(['event', 'ambassador'])
            ->where('check_in_ready_notification_sent', false)
            ->where('status', '1')
            ->where(function ($q) {
                $q->whereNull('event_date_time');
            })
            ->whereHas('event', function ($q) {
                $q->whereNotIn('event_status', ['1', '2'])
                    ->whereNotNull('start_time');
            })
            ->get();

        foreach ($assignments as $assignment) {
            if ($this->notifyIfReady($assignment)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    private function notifyIfReady(AssignAmbassador $assignment): bool
    {
        $event = $assignment->event;
        $ambassador = $assignment->ambassador;

        if (! $event || ! $ambassador) {
            return false;
        }

        if (! $event->hasRecapStartTimePassed()) {
            return false;
        }

        if (! $this->eventCheckInService->isEligibleForCheckIn($event, $assignment)) {
            return false;
        }

        $title = 'Event ready for check-in';
        $message = 'Your event is ready for check-in.';

        try {
            $this->notificationDispatch->dispatch(
                null,
                (int) $ambassador->id,
                'event_check_in_ready',
                $title,
                $message,
                '<p>' . e($message) . '</p>',
                (int) $event->id
            );

            $assignment->check_in_ready_notification_sent = true;
            $assignment->check_in_ready_notification_sent_at = now();
            $assignment->save();

            return true;
        } catch (\Throwable $e) {
            Log::warning('Check-in ready notification failed', [
                'assignment_id' => (int) $assignment->id,
                'event_id' => (int) $event->id,
                'ambassador_id' => (int) $ambassador->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
