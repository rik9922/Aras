<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EventTimezoneService
{
    /** @var array<string, string> */
    private const LEGACY_ABBREV_TO_IANA = [
        'EST' => 'America/New_York',
        'EDT' => 'America/New_York',
        'ET' => 'America/New_York',
        'CST' => 'America/Chicago',
        'CDT' => 'America/Chicago',
        'CT' => 'America/Chicago',
        'MST' => 'America/Denver',
        'MDT' => 'America/Denver',
        'MT' => 'America/Denver',
        'PST' => 'America/Los_Angeles',
        'PDT' => 'America/Los_Angeles',
        'PT' => 'America/Los_Angeles',
    ];

    /** @var array<string, string> */
    private const REGION_NAMES = [
        'America/New_York' => 'Eastern',
        'America/Chicago' => 'Central',
        'America/Denver' => 'Mountain',
        'America/Phoenix' => 'Mountain',
        'America/Los_Angeles' => 'Pacific',
        'America/Anchorage' => 'Alaska',
        'America/Honolulu' => 'Hawaii',
        'America/Detroit' => 'Eastern',
        'America/Indiana/Indianapolis' => 'Eastern',
        'America/Kentucky/Louisville' => 'Eastern',
        'America/Boise' => 'Mountain',
    ];

    /**
     * @return array{timezone: string, timezone_label: string, display: string}|null
     */
    public function resolveFromCoordinates(float $latitude, float $longitude, ?string $at = null): ?array
    {
        $iana = $this->lookupIanaFromGoogle($latitude, $longitude);
        if ($iana === null) {
            return null;
        }

        return $this->buildResolved($iana, $at);
    }

    /**
     * Normalize stored timezone to a canonical IANA identifier.
     */
    public function normalizeIana(?string $timezone): string
    {
        $timezone = trim((string) $timezone);
        if ($timezone === '') {
            return 'UTC';
        }

        $upper = strtoupper($timezone);
        if (isset(self::LEGACY_ABBREV_TO_IANA[$upper])) {
            return self::LEGACY_ABBREV_TO_IANA[$upper];
        }

        try {
            new \DateTimeZone($timezone);

            return $timezone;
        } catch (\Exception) {
            return 'UTC';
        }
    }

    /**
     * DST-aware abbreviation (EST/EDT, CST/CDT, …) for a wall-clock instant in the event zone.
     */
    public function abbreviationAt(string $iana, ?string $at = null): string
    {
        $iana = $this->normalizeIana($iana);
        $reference = $this->referenceInstant($at, $iana);

        return Carbon::parse($reference, $iana)->format('T');
    }

    /**
     * UI label, e.g. "EST (Eastern)".
     */
    public function displayLabel(string $iana, ?string $abbrev = null, ?string $at = null): string
    {
        $iana = $this->normalizeIana($iana);
        $abbrev = $abbrev ?: $this->abbreviationAt($iana, $at);
        $region = self::REGION_NAMES[$iana] ?? str_replace('_', ' ', str_replace('/', ' / ', preg_replace('#^[^/]+/#', '', $iana)));

        return trim($abbrev . ' (' . $region . ')');
    }

    /**
     * Apply IANA timezone + label from coordinates on event payload.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyToEventData(array $data): array
    {
        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;
        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            $data['timezone'] = $this->normalizeIana($data['timezone'] ?? null);
            $data['timezone_label'] = $this->abbreviationAt(
                $data['timezone'],
                $data['start_time'] ?? null
            );

            return $data;
        }

        $at = isset($data['start_time']) ? (string) $data['start_time'] : null;
        $resolved = $this->resolveFromCoordinates((float) $lat, (float) $lng, $at);
        if ($resolved === null) {
            $data['timezone'] = $this->normalizeIana($data['timezone'] ?? null);
            $data['timezone_label'] = $this->abbreviationAt(
                $data['timezone'],
                $at
            );

            return $data;
        }

        $data['timezone'] = $resolved['timezone'];
        $data['timezone_label'] = $resolved['timezone_label'];

        return $data;
    }

    /**
     * Refresh DST-aware label after start_time is known.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function refreshLabelForEventData(array $data): array
    {
        $iana = $this->normalizeIana($data['timezone'] ?? null);
        $data['timezone'] = $iana;
        $data['timezone_label'] = $this->abbreviationAt($iana, $data['start_time'] ?? null);

        return $data;
    }

    /**
     * Wall-clock date/time strings for API/list responses (never shifted to viewer timezone).
     *
     * @return array<string, mixed>
     */
    public function formatEventTimesForApi(Event $event): array
    {
        $iana = $this->normalizeIana($event->timezone);
        $label = $event->timezone_label ?: $this->abbreviationAt($iana, $event->start_time);

        $result = [
            'timezone' => $iana,
            'timezone_label' => $label,
            'time_display' => $this->formatEventTimeRange($event),
            'formatted_time' => $event->formatted_time,
            'formatted_time_with_date' => $event->formatted_time_with_date,
        ];

        if (! empty($event->start_time)) {
            $start = Carbon::parse($event->start_time, $iana);
            $result['start_date'] = $start->format('M d, Y');
            $result['day'] = $start->format('l');
            $result['start_time'] = $start->format('h:i A') . ' ' . $label;
            if (! empty($event->end_time)) {
                $end = Carbon::parse($event->end_time, $iana);
                $result['end_date'] = $end->format('M d, Y');
                $result['end_time'] = $end->format('h:i A') . ' ' . $label;
            }
        }

        if (! empty($event->booked_date)) {
            $result['booked_date'] = Carbon::parse($event->booked_date)->format('M d, Y');
        }

        return $result;
    }

    public function formatEventTimeRange(Event $event): ?string
    {
        if (empty($event->start_time) || empty($event->end_time)) {
            return null;
        }

        $iana = $this->normalizeIana($event->timezone);
        $start = Carbon::parse($event->start_time, $iana);
        $end = Carbon::parse($event->end_time, $iana);
        $label = $event->timezone_label ?: $this->abbreviationAt($iana, $event->start_time);

        return $start->format('M j, g:i A') . ' – ' . $end->format('g:i A') . ' ' . $label;
    }

    /**
     * Calendar-friendly payload: local wall times with per-event IANA zone.
     *
     * @return array<string, mixed>
     */
    public function calendarEventPayload(Event $event): array
    {
        $iana = $this->normalizeIana($event->timezone);
        $label = $event->timezone_label ?: $this->abbreviationAt($iana, $event->start_time);

        $startDisplay = null;
        $endDisplay = null;
        if (! empty($event->start_time)) {
            $start = Carbon::parse($event->start_time, $iana);
            $startDisplay = $start->format('g:i A');
            if (! empty($event->end_time)) {
                $end = Carbon::parse($event->end_time, $iana);
                $endDisplay = $end->format('g:i A');
            }
        }

        return [
            'id' => $event->id,
            'event_name' => $event->event_name,
            'brand' => $event->brand,
            'start_time' => $event->start_time,
            'end_time' => $event->end_time,
            'start' => $this->calendarDateTime($event->start_time),
            'end' => $this->calendarDateTime($event->end_time),
            'timeZone' => $iana,
            'timezone_label' => $label,
            'start_display' => $startDisplay,
            'end_display' => $endDisplay,
            'time_display' => ($startDisplay && $endDisplay)
                ? ($startDisplay . ' – ' . $endDisplay . ' ' . $label)
                : ($startDisplay ? ($startDisplay . ' ' . $label) : null),
            'position' => $event->position ?? 0,
            'kit' => $event->kit ?? 0,
            'age_restricted' => $event->age_restricted ?? 0,
            'ambassador_id' => $event->ambassador_id ?? null,
            'event_status' => $event->event_status ?? null,
        ];
    }

    private function calendarDateTime(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $normalized = str_replace(' ', 'T', trim($value));

        return strlen($normalized) === 16 ? $normalized . ':00' : $normalized;
    }

    /**
     * @return array{timezone: string, timezone_label: string, display: string}
     */
    private function buildResolved(string $iana, ?string $at = null): array
    {
        $iana = $this->normalizeIana($iana);
        $label = $this->abbreviationAt($iana, $at);

        return [
            'timezone' => $iana,
            'timezone_label' => $label,
            'display' => $this->displayLabel($iana, $label, $at),
        ];
    }

    private function referenceInstant(?string $at, string $iana): string
    {
        if (! empty($at)) {
            return (string) $at;
        }

        return Carbon::now($iana)->format('Y-m-d H:i:s');
    }

    private function lookupIanaFromGoogle(float $latitude, float $longitude): ?string
    {
        $apiKey = googleMapsServerKey();
        if ($apiKey === '') {
            Log::warning('Google timezone lookup skipped: GOOGLE_MAPS_API_KEY is not configured.');

            return null;
        }

        try {
            $response = Http::timeout(10)->get('https://maps.googleapis.com/maps/api/timezone/json', [
                'location' => $latitude . ',' . $longitude,
                'timestamp' => time(),
                'key' => $apiKey,
            ]);

            if (! $response->successful()) {
                return null;
            }

            $payload = $response->json();
            if (($payload['status'] ?? '') !== 'OK' || empty($payload['timeZoneId'])) {
                Log::warning('Google timezone lookup returned no result', [
                    'status' => $payload['status'] ?? null,
                    'lat' => $latitude,
                    'lng' => $longitude,
                ]);

                return null;
            }

            return (string) $payload['timeZoneId'];
        } catch (\Throwable $e) {
            Log::warning('Google timezone lookup failed', [
                'error' => $e->getMessage(),
                'lat' => $latitude,
                'lng' => $longitude,
            ]);

            return null;
        }
    }
}
