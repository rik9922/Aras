<?php

namespace App\Services;

use App\Models\AssignAmbassador;
use App\Models\Event;
use App\Services\EventTimezoneService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class EventCheckInService
{
    public function __construct(
        private readonly GeoLocationService $geoLocation,
        private readonly NotificationDispatchService $notificationDispatch,
        private readonly AmbassadorNotificationService $ambassadorNotifications
    ) {}

    /**
     * @return array{ok: true, assignment: AssignAmbassador, first_check_in: bool, is_at_event_location: bool, distance_meters: ?float}
     * @return array{ok: false, code: string, message: string, distance_meters?: float, allowed_radius_meters?: int}
     */
    public function processCheckIn(
        User $ambassador,
        Event $event,
        AssignAmbassador $assignment,
        ?float $baLatitude,
        ?float $baLongitude,
        bool $forceCheckIn = false,
        ?string $checkInTime = null
    ): array {
        $eventCoords = $this->resolveEventCoordinates($event);
        if ($eventCoords === null) {
            return [
                'ok' => false,
                'code' => 'event_location_missing',
                'message' => 'Event location is not configured. Contact your manager.',
            ];
        }

        if ($baLatitude === null || $baLongitude === null) {
            return [
                'ok' => false,
                'code' => 'location_required',
                'message' => 'Current latitude and longitude are required for check-in.',
            ];
        }

        [$eventLat, $eventLng] = $eventCoords;
        $radiusMeters = (int) config('attendance.check_in_radius_meters', 50);
        $distanceMeters = $this->geoLocation->distanceInMeters(
            $baLatitude,
            $baLongitude,
            $eventLat,
            $eventLng
        );
        $isWithinRadius = $distanceMeters <= $radiusMeters;

        if (! $isWithinRadius && ! $forceCheckIn) {
            return [
                'ok' => false,
                'code' => 'outside_event_radius',
                'message' => 'BA is outside event location radius.',
                'distance_meters' => $distanceMeters,
                'allowed_radius_meters' => $radiusMeters,
                'requires_confirmation' => true,
            ];
        }

        $wasCheckedIn = ! empty($assignment->event_date_time);
        $isAtEventLocation = $isWithinRadius;

        $assignment->latitude = $baLatitude;
        $assignment->longitude = $baLongitude;
        $assignment->check_in_event_latitude = $eventLat;
        $assignment->check_in_event_longitude = $eventLng;
        $assignment->distance_from_event_meters = (int) round($distanceMeters);
        $assignment->is_at_event_location = $isAtEventLocation;
        $assignment->forced_check_in = ! $isWithinRadius && $forceCheckIn;
        $assignment->event_date_time = $checkInTime ?? $assignment->event_date_time ?? now()->toDateTimeString();
        $assignment->save();

        if (! $wasCheckedIn) {
            $this->ambassadorNotifications->eventCheckIn($ambassador, $event, $assignment);
            $this->notifyManagerOnCheckIn($ambassador, $event, $assignment, $isAtEventLocation);
        }

        return [
            'ok' => true,
            'assignment' => $assignment,
            'first_check_in' => ! $wasCheckedIn,
            'is_at_event_location' => $isAtEventLocation,
            'distance_meters' => $distanceMeters,
            'forced_check_in' => (bool) $assignment->forced_check_in,
        ];
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    public function resolveEventCoordinates(Event $event): ?array
    {
        if ($event->latitude !== null && $event->longitude !== null) {
            return [(float) $event->latitude, (float) $event->longitude];
        }

        $event->loadMissing('location');
        $venue = $event->location;
        if ($venue !== null) {
            $coords = app(EventLocationService::class)->resolveVenueCoordinates($venue);
            if ($coords !== null) {
                return $coords;
            }
        }

        return null;
    }

    /**
     * True when the event is within its active check-in window (start reached, not yet ended).
     */
    public function isWithinCheckInWindow(Event $event): bool
    {
        if (empty($event->start_time)) {
            return false;
        }

        $timezone = app(EventTimezoneService::class)->normalizeIana($event->timezone);
        $now = Carbon::now($timezone);
        $start = Carbon::parse($event->start_time, $timezone);

        if ($now->lt($start)) {
            return false;
        }

        if (! empty($event->end_time)) {
            $end = Carbon::parse($event->end_time, $timezone);
            if ($now->gt($end)) {
                return false;
            }
        }

        return true;
    }

    public function isEligibleForCheckIn(Event $event, AssignAmbassador $assignment): bool
    {
        if ((string) $assignment->status !== '1') {
            return false;
        }

        if (! empty($assignment->event_date_time)) {
            return false;
        }

        if (in_array((string) $event->event_status, ['1', '2'], true)) {
            return false;
        }

        return $this->isWithinCheckInWindow($event);
    }

    public function eventLocationLabel(Event $event): string
    {
        if (! empty($event->event_address)) {
            return (string) $event->event_address;
        }

        $event->loadMissing('location');
        if ($event->location !== null) {
            $label = app(EventLocationService::class)->formatVenueAddress($event->location);
            if ($label !== '' && $label !== 'Event location') {
                return $label;
            }
        }

        return 'the event destination';
    }

    public function buildLateCheckInEmailHtml(
        User $ambassador,
        Event $event,
        string $summary,
        int $graceMinutes
    ): string {
        $event->loadMissing(['brand', 'eventType', 'department', 'distributor', 'location']);

        $locationLabel = $this->eventLocationLabel($event);
        $timezones = app(EventTimezoneService::class);
        $eventTimezone = $timezones->normalizeIana($event->timezone);
        $label = $event->timezone_label ?: $timezones->abbreviationAt($eventTimezone, $event->start_time);
        $scheduledStartTime = ! empty($event->start_time)
            ? Carbon::parse($event->start_time, $eventTimezone)->format('M j, Y g:i A') . ' ' . $label
            : null;

        $checkInDetail = 'No check-in recorded. The '
            . $graceMinutes
            . '-minute grace period after the scheduled start time has passed.';

        return View::make('email.event-late-check-in', [
            'summary' => $summary,
            'ambassadorName' => trim($ambassador->name ?? 'Ambassador'),
            'ambassadorEmail' => $ambassador->email ?? null,
            'ambassadorPhone' => $ambassador->phone ?? null,
            'checkInStatusLabel' => 'Not checked in',
            'scheduledStartTime' => $scheduledStartTime,
            'checkInDetail' => $checkInDetail,
            'eventId' => (int) $event->id,
            'eventName' => (string) ($event->event_name ?? 'Event'),
            'eventType' => $event->eventType?->name,
            'brandName' => $event->brand?->brand_name,
            'eventDate' => $this->formatEventDate($event),
            'eventTime' => $this->formatEventTimeRange($event),
            'eventLocation' => $locationLabel,
            'department' => $event->department?->name,
            'distributor' => $event->distributor?->name,
            'paymentType' => $this->formatPaymentType($event->payment_type ?? null),
            'eventStatus' => $this->formatEventStatus($event->event_status ?? null),
            'eventUrl' => rtrim((string) config('app.url'), '/') . '/manager/edit-event/' . (int) $event->id,
        ])->render();
    }

    private function notifyManagerOnCheckIn(
        User $ambassador,
        Event $event,
        AssignAmbassador $assignment,
        bool $isAtEventLocation
    ): void {
        $managerId = (int) ($event->manager_id ?? 0);
        if (! $managerId) {
            Log::warning('Check-in manager notification skipped: event has no manager_id', [
                'event_id' => (int) $event->id,
                'ambassador_id' => (int) $ambassador->id,
            ]);

            return;
        }

        try {
            $event->loadMissing(['brand', 'eventType', 'department', 'distributor', 'location']);

            $baName = trim($ambassador->name ?? 'Ambassador');
            $locationLabel = $this->eventLocationLabel($event);

            if ($isAtEventLocation) {
                $message = "{$baName} has checked in at {$locationLabel}";
            } else {
                $message = "{$baName} checked in, but is not at {$locationLabel}";
            }

            $title = 'Ambassador checked in — Event #' . (int) $event->id;
            $emailHtml = $this->buildCheckInEmailHtml(
                $ambassador,
                $event,
                $assignment,
                $isAtEventLocation,
                $message,
                $locationLabel
            );

            $this->notificationDispatch->dispatch(
                (int) $ambassador->id,
                $managerId,
                'event_check_in',
                $title,
                $message,
                $emailHtml,
                (int) $event->id
            );
        } catch (\Throwable $e) {
            Log::warning('Manager check-in notification failed', [
                'event_id' => (int) $event->id,
                'ambassador_id' => (int) $ambassador->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function buildCheckInEmailHtml(
        User $ambassador,
        Event $event,
        AssignAmbassador $assignment,
        bool $isAtEventLocation,
        string $summary,
        string $locationLabel
    ): string {
        $distanceMeters = $assignment->distance_from_event_meters;
        $radiusMeters = (int) config('attendance.check_in_radius_meters', 50);
        $distanceNote = null;

        if ($isAtEventLocation) {
            $locationStatus = 'at_event';
            $locationStatusLabel = 'At event location';
            if ($distanceMeters !== null) {
                $distanceNote = 'Within ' . (int) $distanceMeters . ' m of the event (allowed radius: ' . $radiusMeters . ' m).';
            }
        } else {
            $locationStatus = 'away_from_event';
            $locationStatusLabel = 'Not at event location';
            if ($distanceMeters !== null) {
                $distanceNote = 'Approximately ' . (int) $distanceMeters . ' m from the event (allowed radius: ' . $radiusMeters . ' m).';
            }
            if ((bool) $assignment->forced_check_in) {
                $distanceNote = trim(($distanceNote ?? '') . ' The ambassador confirmed check-in while off-site.');
            }
        }

        $checkInTime = $assignment->event_date_time
            ? Carbon::parse($assignment->event_date_time)->format('M j, Y g:i A T')
            : now()->format('M j, Y g:i A T');

        return View::make('email.event-check-in', [
            'summary' => $summary,
            'ambassadorName' => trim($ambassador->name ?? 'Ambassador'),
            'ambassadorEmail' => $ambassador->email ?? null,
            'ambassadorPhone' => $ambassador->phone ?? null,
            'checkInTime' => $checkInTime,
            'locationStatus' => $locationStatus,
            'locationStatusLabel' => $locationStatusLabel,
            'distanceNote' => $distanceNote,
            'eventId' => (int) $event->id,
            'eventName' => (string) ($event->event_name ?? 'Event'),
            'eventType' => $event->eventType?->name,
            'brandName' => $event->brand?->brand_name,
            'eventDate' => $this->formatEventDate($event),
            'eventTime' => $this->formatEventTimeRange($event),
            'eventLocation' => $locationLabel,
            'department' => $event->department?->name,
            'distributor' => $event->distributor?->name,
            'paymentType' => $this->formatPaymentType($event->payment_type ?? null),
            'eventStatus' => $this->formatEventStatus($event->event_status ?? null),
            'eventUrl' => rtrim((string) config('app.url'), '/') . '/manager/edit-event/' . (int) $event->id,
        ])->render();
    }

    private function formatEventDate(Event $event): ?string
    {
        if (empty($event->booked_date)) {
            return null;
        }

        return Carbon::parse($event->booked_date)->format('l, M j, Y');
    }

    private function formatEventTimeRange(Event $event): ?string
    {
        $formatted = app(EventTimezoneService::class)->formatEventTimeRange($event);
        if ($formatted === null) {
            return null;
        }

        $iana = app(EventTimezoneService::class)->normalizeIana($event->timezone);
        $start = Carbon::parse($event->start_time, $iana);
        $end = Carbon::parse($event->end_time, $iana);
        $duration = $start->diff($end);
        $totalHours = str_pad((string) $duration->h, 2, '0', STR_PAD_LEFT) . ':'
            . str_pad((string) $duration->i, 2, '0', STR_PAD_LEFT) . ' Hrs';

        return $formatted . ' — Total: ' . $totalHours;
    }

    private function formatPaymentType(mixed $type): ?string
    {
        $map = [
            '1' => 'Cash',
            '2' => 'Check',
            '3' => 'Credit Card',
            '4' => 'Debit Card',
        ];

        $key = (string) $type;

        return $map[$key] ?? ($type ? (string) $type : null);
    }

    private function formatEventStatus(mixed $status): ?string
    {
        return match ((string) $status) {
            '1' => 'Completed',
            '2' => 'Assigned',
            '0' => 'Pending',
            default => $status !== null && $status !== '' ? (string) $status : null,
        };
    }
}
