<?php

namespace App\Services;

use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

class EventAssignmentNotificationService
{
    public const NOTIFICATION_TYPE = 'event_assignment';

    public function __construct(
        private readonly NotificationDispatchService $notificationDispatch
    ) {}

    public function notifyApprovedAssignment(AssignAmbassador $assignment): void
    {
        $assignment->loadMissing(['event.location', 'ambassador']);

        $event = $assignment->event;
        $ambassador = $assignment->ambassador;

        if (! $event || ! $ambassador) {
            Log::warning('Event assignment notification skipped: missing event or ambassador', [
                'assignment_id' => (int) $assignment->id,
            ]);

            return;
        }

        $eventName = (string) ($event->event_name ?? 'Event');
        $title = 'Assigned Ambassador';
        $message = "You have been approved as an ambassador for {$eventName}.";

        try {
            if ($this->alreadySent((int) $ambassador->id, (int) $event->id)) {
                return;
            }

            $this->notificationDispatch->dispatch(
                null,
                (int) $ambassador->id,
                self::NOTIFICATION_TYPE,
                $title,
                $message,
                $this->buildAssignmentEmailHtml($assignment, $event, $ambassador),
                (int) $event->id
            );
        } catch (\Throwable $e) {
            Log::warning('Event assignment notification failed', [
                'assignment_id' => (int) $assignment->id,
                'event_id' => (int) $event->id,
                'ambassador_id' => (int) $ambassador->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function alreadySent(int $ambassadorId, int $eventId): bool
    {
        if (! Schema::hasTable('notifications')) {
            return false;
        }

        return Notification::query()
            ->where('receiver_id', $ambassadorId)
            ->where('type', self::NOTIFICATION_TYPE)
            ->where('booking_id', $eventId)
            ->exists();
    }

    private function buildAssignmentEmailHtml(AssignAmbassador $assignment, Event $event, User $ambassador): string
    {
        return View::make(
            'email.partials.assigned-event-body',
            $this->buildAssignmentEmailData($assignment, $event, $ambassador)
        )->render();
    }

    /**
     * @return array{event_name: string, event_date: string, location: string, name: string, app_download_url: string}
     */
    public function buildAssignmentEmailData(AssignAmbassador $assignment, Event $event, User $ambassador): array
    {
        $eventWithLocation = $event->relationLoaded('location')
            ? $event
            : Event::with('location')->find($event->id);

        return [
            'event_name' => $assignment->event?->event_name ?? $event->event_name ?? '-',
            'event_date' => $this->formatAssignmentDate($event),
            'location' => $eventWithLocation?->location?->address ?? '-',
            'name' => $ambassador->name ?? '-',
            'app_download_url' => (string) config('services.app_download.url', ''),
        ];
    }

    /**
     * Format the event date/time for the assignment email in the event's assigned
     * timezone, using a 12-hour clock with the timezone label
     * (e.g. "August 29, 2026, 1:00 PM To 3:00 PM CDT").
     *
     * Replaces the previous raw datetimes ("2026-08-29 13:00:00 …") that testers
     * flagged as military time in the wrong timezone.
     */
    private function formatAssignmentDate(Event $event): string
    {
        if (empty($event->start_time) || empty($event->end_time)) {
            return '-';
        }

        $timezones = app(EventTimezoneService::class);
        $iana = $timezones->normalizeIana($event->timezone);
        $start = Carbon::parse($event->start_time, $iana);
        $end = Carbon::parse($event->end_time, $iana);
        $label = $event->timezone_label ?: $timezones->abbreviationAt($iana, $event->start_time);

        return $start->format('F j, Y, g:i A').' To '.$end->format('g:i A').' '.$label;
    }
}
