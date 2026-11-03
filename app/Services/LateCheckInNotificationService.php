<?php

namespace App\Services;

use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\User;
use App\Services\EventTimezoneService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LateCheckInNotificationService
{
    public function __construct(
        private readonly NotificationDispatchService $notificationDispatch,
        private readonly EventCheckInService $eventCheckInService
    ) {}

    /**
     * Notify managers and BAs who have not checked in within the grace period after event start.
     */
    public function processDueAssignments(): int
    {
        $graceMinutes = (int) config('attendance.late_checkin_grace_minutes', 10);
        $sentCount = 0;

        $assignments = AssignAmbassador::query()
            ->with(['event', 'ambassador'])
            ->where('late_checkin_notification_sent', false)
            ->where(function ($q) {
                $q->whereNull('event_date_time');
            })
            ->whereHas('event', function ($q) {
                $q->where('event_status', '0')
                    ->whereNotNull('start_time');
            })
            ->get();

        foreach ($assignments as $assignment) {
            if ($this->notifyIfLate($assignment, $graceMinutes)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    private function notifyIfLate(AssignAmbassador $assignment, int $graceMinutes): bool
    {
        $event = $assignment->event;
        $ambassador = $assignment->ambassador;

        if (! $event || ! $ambassador) {
            return false;
        }

        if (! $this->isPastGracePeriod($event, $graceMinutes)) {
            return false;
        }

        if (! empty($assignment->event_date_time)) {
            return false;
        }

        $locationLabel = $this->eventCheckInService->eventLocationLabel($event);
        $baName = trim($ambassador->name ?? 'Ambassador');

        $managerMessage = "{$baName} has not checked in for {$locationLabel} within {$graceMinutes} minutes of the scheduled start time.";
        $baMessage = "You have not checked in for your scheduled event at {$locationLabel}. Please check in immediately.";

        $title = 'Late event check-in';

        try {
            $managerId = (int) ($event->manager_id ?? 0);
            if ($managerId) {
                $this->notificationDispatch->dispatch(
                    (int) $ambassador->id,
                    $managerId,
                    'event_late_check_in',
                    $title,
                    $managerMessage,
                    $this->eventCheckInService->buildLateCheckInEmailHtml(
                        $ambassador,
                        $event,
                        $managerMessage,
                        $graceMinutes
                    ),
                    (int) $event->id
                );
            }

            $this->notificationDispatch->dispatch(
                $managerId ?: null,
                (int) $ambassador->id,
                'event_late_check_in',
                $title,
                $baMessage,
                '<p>' . e($baMessage) . '</p>',
                (int) $event->id
            );

            $assignment->late_checkin_notification_sent = true;
            $assignment->late_checkin_notification_sent_at = now();
            $assignment->save();

            return true;
        } catch (\Throwable $e) {
            Log::warning('Late check-in notification failed', [
                'assignment_id' => (int) $assignment->id,
                'event_id' => (int) $event->id,
                'ambassador_id' => (int) $ambassador->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function isPastGracePeriod(Event $event, int $graceMinutes): bool
    {
        if (empty($event->start_time)) {
            return false;
        }

        $timezone = app(EventTimezoneService::class)->normalizeIana($event->timezone);
        $start = Carbon::parse($event->start_time, $timezone);
        $alertAfter = $start->copy()->addMinutes($graceMinutes);

        return now($timezone)->greaterThanOrEqualTo($alertAfter);
    }
}
