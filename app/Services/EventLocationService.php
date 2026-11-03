<?php

namespace App\Services;

use App\Models\EventLocation;

class EventLocationService
{
    public function __construct(
        private readonly GoogleGeocodingService $geocoding,
        private readonly EventTimezoneService $timezones
    ) {}

    /**
     * Ensure event latitude/longitude are set from event_address when missing.
     */
    public function applyCoordinatesFromAddress(array $data): array
    {
        $address = trim((string) ($data['event_address'] ?? ''));
        if ($address === '') {
            return $data;
        }

        $hasLat = isset($data['latitude']) && $data['latitude'] !== '' && $data['latitude'] !== null;
        $hasLng = isset($data['longitude']) && $data['longitude'] !== '' && $data['longitude'] !== null;
        if ($hasLat && $hasLng) {
            return $this->applyTimezoneFromCoordinates($data);
        }

        $coords = $this->geocoding->geocodeAddress($address);
        if ($coords === null) {
            return $data;
        }

        $data['latitude'] = $coords['lat'];
        $data['longitude'] = $coords['lng'];

        return $this->applyTimezoneFromCoordinates($data);
    }

    /**
     * Resolve event timezone + label from coordinates (and optional start_time for DST).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyTimezoneFromCoordinates(array $data): array
    {
        $data = $this->timezones->applyToEventData($data);

        return $this->timezones->refreshLabelForEventData($data);
    }

    /**
     * Apply timezone fields to a venue (event_locations) payload from coordinates.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyTimezoneToVenueData(array $data): array
    {
        $data = $this->timezones->applyToEventData($data);

        return $data;
    }

    /**
     * Copy venue address, coordinates, and timezone onto the event payload (denormalized snapshot).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function inheritFromVenue(array $data): array
    {
        $venueId = $data['event_location_id'] ?? null;
        if ($venueId === null || $venueId === '') {
            return $data;
        }

        $venue = EventLocation::find($venueId);
        if ($venue === null) {
            return $data;
        }

        $data['event_address'] = $this->formatVenueAddress($venue);

        if ($venue->latitude !== null && $venue->latitude !== '' && $venue->longitude !== null && $venue->longitude !== '') {
            $data['latitude'] = $venue->latitude;
            $data['longitude'] = $venue->longitude;
        }

        if (! empty($venue->timezone)) {
            $data['timezone'] = $venue->timezone;
            $data['timezone_label'] = $venue->timezone_label;
        }

        if (! empty($venue->city)) {
            $data['city'] = $venue->city;
        }

        return $data;
    }

    /**
     * Full event location pipeline: inherit from venue, then ensure coords/timezone.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyEventLocationFromVenue(array $data): array
    {
        $data = $this->inheritFromVenue($data);
        $data = $this->applyCoordinatesFromAddress($data);
        $data = $this->applyTimezoneFromCoordinates($data);

        return $data;
    }

    public function formatVenueAddress(EventLocation $venue): string
    {
        $parts = array_filter([
            trim((string) ($venue->address ?? '')),
            trim((string) ($venue->city ?? '')),
            trim((string) ($venue->state ?? '')),
            trim((string) ($venue->zip_code ?? '')),
        ], fn ($part) => $part !== '');

        if ($parts !== []) {
            return implode(', ', $parts);
        }

        $name = trim((string) ($venue->name ?? ''));

        return $name !== '' ? $name : 'Event location';
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    public function resolveVenueCoordinates(EventLocation $venue): ?array
    {
        if ($venue->latitude !== null && $venue->latitude !== ''
            && $venue->longitude !== null && $venue->longitude !== '') {
            return [(float) $venue->latitude, (float) $venue->longitude];
        }

        $address = $this->formatVenueAddress($venue);
        if ($address === '' || $address === 'Event location') {
            return null;
        }

        $coords = $this->geocoding->geocodeAddress($address);
        if ($coords === null) {
            return null;
        }

        return [(float) $coords['lat'], (float) $coords['lng']];
    }
}
