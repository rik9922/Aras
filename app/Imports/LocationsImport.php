<?php

namespace App\Imports;

use App\Models\EventLocation;
use App\Services\GoogleGeocodingService;
use App\Services\EventLocationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports Event Locations from CSV.
 *
 * CSV columns: Location, Address, City, State, Zip, Store Number
 * Maatwebsite WithHeadingRow slugifies headings, e.g.:
 *   "Location"     → 'location'
 *   "Address"      → 'address'
 *   "City"         → 'city'
 *   "State"        → 'state'
 *   "Zip"          → 'zip'
 *   "Store Number" → 'store_number'
 *
 * For each row the full address is geocoded via GoogleGeocodingService
 * (same service used by /admin/event-location/create), then timezone is
 * resolved via EventLocationService::applyTimezoneToVenueData().
 *
 * Uniqueness: name + address + city + state + zip_code (case-insensitive).
 *
 * WithChunkReading intentionally omitted — serialization resets counters.
 */
class LocationsImport implements ToCollection, WithHeadingRow
{
    public int $imported      = 0;
    public int $skipped       = 0;   // already exist in DB
    public int $empty         = 0;   // missing required fields
    public int $geoFailed     = 0;   // geocoding returned no result
    public int $insertFailed  = 0;   // DB create() threw

    public function __construct(
        private readonly GoogleGeocodingService $geocoding,
        private readonly EventLocationService   $locationService,
    ) {}

    public function collection(Collection $rows): void
    {
        Log::info('[LocationsImport] STAGE 7a — collection() called', [
            'total_rows'       => $rows->count(),
            'first_row_keys'   => $rows->isNotEmpty() ? array_keys($rows->first()->toArray()) : [],
            'first_row_sample' => $rows->isNotEmpty() ? $rows->first()->toArray() : [],
        ]);

        if ($rows->isEmpty()) {
            Log::warning('[LocationsImport] STAGE 7a — collection is empty');
            return;
        }

        // ── Build a normalised uniqueness key for every existing location ──
        // Avoids N+1 by loading all names into memory once.
        $existingKeys = EventLocation::query()
            ->select(['name', 'address', 'city', 'state', 'zip_code'])
            ->get()
            ->map(fn ($loc) => $this->uniqueKey(
                $loc->name, $loc->address, $loc->city, $loc->state, $loc->zip_code
            ))
            ->flip()
            ->all();

        Log::info('[LocationsImport] STAGE 7b — Existing locations loaded', [
            'existing_count' => count($existingKeys),
        ]);

        $seenThisRun = [];

        foreach ($rows as $index => $row) {
            $rowNum = $index + 2; // 1-based, +1 for header row

            // ── Extract CSV fields ─────────────────────────────────────────
            // Maatwebsite slugifies multi-word headings:
            //   "Store Number" → 'store_number'  (spaces → underscores)
            $name        = trim((string) ($row['location']     ?? ''));
            $address     = trim((string) ($row['address']      ?? ''));
            $city        = trim((string) ($row['city']         ?? ''));
            $state       = trim((string) ($row['state']        ?? ''));
            $zip         = trim((string) ($row['zip']          ?? ''));
            $storeNumber = trim((string) ($row['store_number'] ?? ''));

            // ── Skip rows without a name (minimum required field) ─────────
            if ($name === '') {
                $this->empty++;
                Log::debug('[LocationsImport] Row skipped — empty name', ['row' => $rowNum]);
                continue;
            }

            // ── Intra-file duplicate check ─────────────────────────────────
            $key = $this->uniqueKey($name, $address, $city, $state, $zip);

            if (isset($seenThisRun[$key])) {
                $this->skipped++;
                Log::debug('[LocationsImport] Row skipped — intra-file duplicate', [
                    'row' => $rowNum, 'name' => $name,
                ]);
                continue;
            }

            $seenThisRun[$key] = true;

            // ── DB duplicate check ────────────────────────────────────────
            if (isset($existingKeys[$key])) {
                $this->skipped++;
                Log::debug('[LocationsImport] Row skipped — already in DB', [
                    'row' => $rowNum, 'name' => $name,
                ]);
                continue;
            }

            // ── Geocode the address ───────────────────────────────────────
            $fullAddress = implode(', ', array_filter([$address, $city, $state, $zip]));
            $lat = null;
            $lng = null;

            if ($fullAddress !== '') {
                Log::info('[LocationsImport] Geocoding row', [
                    'row' => $rowNum, 'name' => $name, 'address' => $fullAddress,
                ]);

                $coords = $this->geocoding->geocodeAddress($fullAddress);

                if ($coords !== null) {
                    $lat = $coords['lat'];
                    $lng = $coords['lng'];
                    Log::info('[LocationsImport] Geocode success', [
                        'row' => $rowNum, 'lat' => $lat, 'lng' => $lng,
                    ]);
                } else {
                    $this->geoFailed++;
                    Log::warning('[LocationsImport] Geocode returned no result — importing without coordinates', [
                        'row' => $rowNum, 'name' => $name, 'address' => $fullAddress,
                    ]);
                }
            }

            // ── Resolve timezone from coordinates ─────────────────────────
            // Reuses EventLocationService::applyTimezoneToVenueData() —
            // exactly what EventLocationController::store() calls.
            $timezone      = null;
            $timezoneLabel = null;

            if ($lat !== null && $lng !== null) {
                $venuePayload = $this->locationService->applyTimezoneToVenueData([
                    'latitude'  => $lat,
                    'longitude' => $lng,
                ]);
                $timezone      = $venuePayload['timezone']       ?? null;
                $timezoneLabel = $venuePayload['timezone_label'] ?? null;

                Log::info('[LocationsImport] Timezone resolved', [
                    'row'            => $rowNum,
                    'timezone'       => $timezone,
                    'timezone_label' => $timezoneLabel,
                ]);
            }

            // ── Insert ────────────────────────────────────────────────────
            try {
                EventLocation::create([
                    'name'            => $name,
                    'address'         => $address ?: null,
                    'city'            => $city ?: null,
                    'state'           => $state ?: null,
                    'zip_code'        => $zip ?: null,
                    'store_number'    => $storeNumber ?: null,
                    'latitude'        => $lat !== null ? (string) $lat : null,
                    'longitude'       => $lng !== null ? (string) $lng : null,
                    'timezone'        => $timezone,
                    'timezone_label'  => $timezoneLabel,
                ]);

                // Mark as seen so a later row in this file can't duplicate it
                $existingKeys[$key] = true;
                $this->imported++;

                Log::info('[LocationsImport] Row imported', [
                    'row' => $rowNum, 'name' => $name,
                ]);
            } catch (\Throwable $e) {
                $this->insertFailed++;
                Log::error('[LocationsImport] DB insert failed', [
                    'row'             => $rowNum,
                    'name'            => $name,
                    'exception_class' => get_class($e),
                    'message'         => $e->getMessage(),
                    'file'            => $e->getFile(),
                    'line'            => $e->getLine(),
                ]);
            }
        }

        Log::info('[LocationsImport] STAGE 7d — complete', [
            'imported'     => $this->imported,
            'skipped'      => $this->skipped,
            'empty'        => $this->empty,
            'geo_failed'   => $this->geoFailed,
            'insert_failed' => $this->insertFailed,
        ]);
    }

    /**
     * Case-insensitive composite key for uniqueness comparison.
     */
    private function uniqueKey(
        ?string $name,
        ?string $address,
        ?string $city,
        ?string $state,
        ?string $zip,
    ): string {
        return implode('|', [
            Str::lower(trim((string) $name)),
            Str::lower(trim((string) $address)),
            Str::lower(trim((string) $city)),
            Str::lower(trim((string) $state)),
            Str::lower(trim((string) $zip)),
        ]);
    }
}
