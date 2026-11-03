<?php

namespace App\Imports;

use App\Models\Brand;
use App\Models\Event;
use App\Models\EventDepartment;
use App\Models\EventDistributor;
use App\Models\EventLocation;
use App\Models\EventType;
use App\Models\Market;
use App\Models\User;
use App\Models\Venue;
use App\Services\EventTimezoneService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Chunked Event CSV import using Maatwebsite Laravel Excel.
 *
 * CSV columns (Maatwebsite WithHeadingRow slugifies them):
 *   "Event Id"     → event_id
 *   "Day"          → day
 *   "Start"        → start
 *   "End"          → end
 *   "Hours"        → hours
 *   "Timezone"     → timezone
 *   "Brand"        → brand
 *   "Varietals"    → varietals
 *   "Location"     → location
 *   "Address"      → address
 *   "City"         → city
 *   "State"        → state
 *   "Zip"          → zip
 *   "Store Number" → store_number
 *   "Kit Required" → kit_required
 *   "Distributor"  → distributor
 *   "Ambassador"   → ambassador   (skipped — not imported)
 *   "Email"        → email        (skipped — not imported)
 *   "Phone"        → phone        (skipped — not imported)
 *   "Department"   → department
 *   "Payment Type" → payment_type
 *   "Event Type"   → event_type
 *   "Market"       → market
 *   "Region"       → region
 *   "Manager"      → manager      (optional — lookup only, never create)
 *
 * Duplicate detection: events are identified by event_name = "Event-{EventId}".
 * Re-importing the same CSV is safe — existing events are skipped (not re-created).
 *
 * Ambassador data is intentionally NOT imported.
 * Manager is optional — missing/unmatched manager never blocks import.
 */
class EventsImport implements ToCollection, WithChunkReading, WithHeadingRow
{
    // ── Public counters (read after Excel::import()) ─────────────────────
    public int $totalProcessed    = 0;
    public int $imported          = 0;
    public int $updated           = 0;  // reserved — upsert not used, but tracked if needed
    public int $skipped           = 0;  // duplicates already in DB
    public int $failed            = 0;
    public int $managerMissing    = 0;  // rows where manager was absent or not matched
    public int $clientMissing     = 0;  // rows saved with client_id = NULL (CSV has no client column)
    public int $refNotMatched     = 0;  // rows where any reference lookup returned null
    // Per-reference miss counters (for detailed summary)
    public int $missingBrand      = 0;
    public int $missingDepartment = 0;
    public int $missingDistributor = 0;
    public int $missingEventType  = 0;
    public int $missingMarket     = 0;
    public int $missingLocation   = 0;

    /** @var array<int, array> Row-level failure log */
    public array $failedRows = [];

    // ── Chunk size ────────────────────────────────────────────────────────
    // Maatwebsite serialises the import object between chunks.
    // Counters survive because they are plain int properties.
    public function chunkSize(): int
    {
        return 200;
    }

    // ── Reference-data lookup caches (rebuilt per chunk after deserialise) ─
    // These are populated lazily on first use within each chunk.
    // Lowercase-name → DB id.
    private array $brandCache       = [];
    private array $locationCache    = [];  // composite key → EventLocation|null (full record for geo fields)
    private array $distributorCache = [];
    private array $departmentCache  = [];
    private array $marketCache      = [];
    private array $eventTypeCache   = [];
    private array $managerCache     = [];  // name → id|null

    private bool $cachesLoaded = false;

    // ── EventTimezoneService ──────────────────────────────────────────────
    private ?EventTimezoneService $tzService = null;

    private function tzService(): EventTimezoneService
    {
        if ($this->tzService === null) {
            $this->tzService = app(EventTimezoneService::class);
        }
        return $this->tzService;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Main collection() — called once per chunk
    // ─────────────────────────────────────────────────────────────────────
    public function collection(Collection $rows): void
    {
        // Caches may be empty after Maatwebsite deserialisation — reload.
        $this->ensureCachesLoaded();

        $chunkStart = $this->totalProcessed + 1;

        Log::info('[EventsImport] Chunk started', [
            'rows_in_chunk'  => $rows->count(),
            'running_total'  => $this->totalProcessed,
            'chunk_row_from' => $chunkStart,
        ]);

        foreach ($rows as $index => $row) {
            $csvRowNum = $chunkStart + $index; // approximate CSV row (1-based, header excluded)
            $this->totalProcessed++;

            $eventCsvId = trim((string) ($row['event_id'] ?? ''));

            if ($eventCsvId === '') {
                $this->failed++;
                $this->failedRows[] = [
                    'row'     => $csvRowNum,
                    'event_id' => null,
                    'error'   => 'Missing Event Id — row skipped',
                ];
                Log::warning('[EventsImport] Row skipped — missing Event Id', ['row' => $csvRowNum]);
                continue;
            }

            // Event name used as idempotency key (matches existing pattern)
            $eventName = 'Event-' . $eventCsvId;

            try {
                $this->processRow($row, $eventName, $eventCsvId, $csvRowNum);
            } catch (\Throwable $e) {
                $this->failed++;
                $this->failedRows[] = [
                    'row'             => $csvRowNum,
                    'event_id'        => $eventCsvId,
                    'event_name'      => $eventName,
                    'error'           => $e->getMessage(),
                    'exception_class' => get_class($e),
                ];
                Log::error('[EventsImport] Row exception', [
                    'row'             => $csvRowNum,
                    'event_id'        => $eventCsvId,
                    'exception_class' => get_class($e),
                    'message'         => $e->getMessage(),
                    'file'            => $e->getFile(),
                    'line'            => $e->getLine(),
                ]);
            }
        }

        Log::info('[EventsImport] Chunk complete', [
            'chunk_rows'      => $rows->count(),
            'total_so_far'    => $this->totalProcessed,
            'imported'        => $this->imported,
            'skipped'         => $this->skipped,
            'failed'          => $this->failed,
            'mgr_missing'     => $this->managerMissing,
            'client_missing'  => $this->clientMissing,
            'ref_not_matched' => $this->refNotMatched,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Process a single CSV row
    // ─────────────────────────────────────────────────────────────────────
    private function processRow(
        Collection $row,
        string $eventName,
        string $eventCsvId,
        int $csvRowNum,
    ): void {
        // ── Duplicate check ───────────────────────────────────────────────
        $existing = Event::withTrashed()->where('event_name', $eventName)->first();
        if ($existing !== null) {
            $this->skipped++;
            Log::debug('[EventsImport] Duplicate — skipped', [
                'row'        => $csvRowNum,
                'event_id'   => $eventCsvId,
                'db_id'      => $existing->id,
            ]);
            return;
        }

        // ── Resolve all reference IDs up-front ────────────────────────────
        // Validate every required FK before touching the DB.
        // Any null on a NOT NULL column → skip the row immediately.
        $brandId       = $this->resolveBrand(trim((string) ($row['brand']       ?? '')), $csvRowNum, $eventCsvId);
        $departmentId  = $this->resolveDepartment(trim((string) ($row['department']  ?? '')), $csvRowNum, $eventCsvId);
        $distributorId = $this->resolveDistributor(trim((string) ($row['distributor'] ?? '')), $csvRowNum, $eventCsvId);
        $eventTypeId   = $this->resolveEventType(trim((string) ($row['event_type']  ?? '')), $csvRowNum, $eventCsvId);
        $locationRecord = $this->resolveLocationRecord($row, $csvRowNum, $eventCsvId);
        $locationId    = $locationRecord?->id;
        $marketId      = $this->resolveMarket(trim((string) ($row['market']      ?? '')), $csvRowNum, $eventCsvId);

        // ── Required-FK guard — skip row before any DB write ──────────────
        // These columns are NOT NULL in the events table.
        $missingRefs = [];
        if ($brandId       === null) $missingRefs[] = 'brand ("'       . trim((string) ($row['brand']       ?? '')) . '")';
        if ($departmentId  === null) $missingRefs[] = 'department ("'  . trim((string) ($row['department']  ?? '')) . '")';
        if ($distributorId === null) $missingRefs[] = 'distributor ("' . trim((string) ($row['distributor'] ?? '')) . '")';
        if ($eventTypeId   === null) $missingRefs[] = 'event_type ("'  . trim((string) ($row['event_type']  ?? '')) . '")';
        if ($locationId    === null) $missingRefs[] = 'location ("'    . trim((string) ($row['location']    ?? '')) . '")';
        // locationRecord being null is already covered by locationId === null above

        if (!empty($missingRefs)) {
            $this->failed++;
            $errorMsg = 'Required reference(s) not matched: ' . implode(', ', $missingRefs);
            $this->failedRows[] = [
                'row'             => $csvRowNum,
                'event_id'        => $eventCsvId,
                'event_name'      => $eventName,
                'error'           => $errorMsg,
                'exception_class' => null,
            ];
            Log::error('[EventsImport] Row skipped — required FK(s) unresolved', [
                'row'          => $csvRowNum,
                'event_id'     => $eventCsvId,
                'missing_refs' => $missingRefs,
            ]);
            return;
        }

        // Manager is optional — never blocks import.
        $managerId = $this->resolveManager(trim((string) ($row['manager'] ?? '')), $csvRowNum, $eventCsvId);

        // ── Timezone / Date-Time ──────────────────────────────────────────
        $tzRaw   = trim((string) ($row['timezone'] ?? 'UTC'));
        $iana    = $this->tzService()->normalizeIana($tzRaw);
        $dayRaw  = trim((string) ($row['day']   ?? ''));
        $startRaw = trim((string) ($row['start'] ?? ''));
        $endRaw   = trim((string) ($row['end']   ?? ''));

        $startDt = $this->parseDateTime($dayRaw, $startRaw, $iana, $csvRowNum, $eventCsvId);
        $endDt   = $this->parseDateTime($dayRaw, $endRaw,   $iana, $csvRowNum, $eventCsvId);

        // Timezone label abbreviation resolved from start time
        $tzLabel = $startDt
            ? $this->tzService()->abbreviationAt($iana, $startDt)
            : null;

        // ── Kit Required ──────────────────────────────────────────────────
        $kitRaw = strtolower(trim((string) ($row['kit_required'] ?? 'no')));
        $kit    = in_array($kitRaw, ['yes', '1', 'true'], true) ? 1 : 0;

        // ── Payment Type ──────────────────────────────────────────────────
        $paymentType = trim((string) ($row['payment_type'] ?? '')) ?: null;

        // ── Varietals ────────────────────────────────────────────────────
        $varietals = trim((string) ($row['varietals'] ?? '')) ?: null;

        // ── Region (from CSV, fall back to state-based heuristic) ─────────
        $regionCsv   = trim((string) ($row['region'] ?? ''));
        $stateCsv    = trim((string) ($row['state']  ?? ''));
        $region      = $regionCsv !== '' ? $regionCsv : $this->regionFromState($stateCsv);

        // ── Build address note ────────────────────────────────────────────
        $notes = $this->buildAddressNote($row);

        // ── Insert Event ──────────────────────────────────────────────────
        $event = Event::create([
            'event_name'        => $eventName,
            'event_type_id'     => $eventTypeId,
            'event_location_id' => $locationId,

            // Geo fields copied from the matched event_locations record (no geocoding)
            'event_address'     => $locationRecord?->address ?: null,
            'city'              => $locationRecord?->city     ?: null,
            'latitude'          => $locationRecord?->latitude ?: null,
            'longitude'         => $locationRecord?->longitude ?: null,
            'department_id'     => $departmentId,
            'distributor_id'    => $distributorId,
            'brand_id'          => $brandId,
            'market_id'         => $marketId,
            'manager_id'        => $managerId,

            // Ambassador intentionally left empty
            'ambassador_id'     => '',

            // client_id: CSV has no client column — always NULL; tracked in clientMissing counter
            'client_id'         => null,

            'start_time'        => $startDt,
            'end_time'          => $endDt,
            'timezone'          => $iana,
            'timezone_label'    => $tzLabel,

            'varietals'         => $varietals,
            'notes'             => $notes,
            'payment_type'      => $paymentType,
            'kit'               => $kit,
            'region'            => $region,

            // Defaults matching existing event create flow
            'position'          => 1,
            'age_restricted'    => 0,
            'apply_event'       => 1,
            'payment_status'    => 0,
            'expedite_fee'      => 0,
            'shipping_fee'      => 0,
            'parking_fee'       => 0,
            'drive_time'        => 0,
            'receipt'           => 0,
            'recap_needed'      => 0,
            'booked_date'       => now()->toDateString(),
            'booked_by'         => 'CSV Import',
        ]);

        // ── Sync market into event_market pivot ────────────────────────────
        if ($marketId !== null) {
            DB::table('event_market')->insertOrIgnore([
                'event_id'   => $event->id,
                'market_id'  => $marketId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Track that client_id was not available (CSV never provides it)
        $this->clientMissing++;

        $this->imported++;

        Log::info('[EventsImport] Event imported', [
            'row'        => $csvRowNum,
            'event_id'   => $eventCsvId,
            'db_id'      => $event->id,
            'event_name' => $eventName,
            'manager_id' => $managerId,   // null = missing/unmatched (import not blocked)
            'client_id'  => null,         // always null — CSV has no client column
            'market_id'  => $marketId,
            'brand_id'   => $brandId,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Reference-data resolvers  (lookup-only, never create)
    // ─────────────────────────────────────────────────────────────────────

    private function resolveBrand(string $name, int $row, string $eventId): ?int
    {
        if ($name === '') {
            $this->refNotMatched++;
            $this->missingBrand++;
            Log::warning('[EventsImport] Reference lookup failed', [
                'row' => $row, 'event_id' => $eventId,
                'type' => 'Brand', 'csv_value' => $name,
                'normalized_value' => '', 'reason' => 'CSV value is empty',
            ]);
            return null;
        }
        $key = $this->normalizeRefName($name);
        if (array_key_exists($key, $this->brandCache)) {
            $id = $this->brandCache[$key];
            if ($id === null) {
                $this->refNotMatched++;
                $this->missingBrand++;
            }
            return $id;
        }
        // Not in cache — miss (cache is fully pre-loaded; unknown means not in DB)
        $this->brandCache[$key] = null;
        $this->refNotMatched++;
        $this->missingBrand++;
        Log::warning('[EventsImport] Reference lookup failed', [
            'row' => $row, 'event_id' => $eventId,
            'type' => 'Brand', 'csv_value' => $name,
            'normalized_value' => $key, 'reason' => 'No matching brand found',
        ]);
        return null;
    }

    /**
     * Resolve the matching EventLocation record for this CSV row.
     * Returns the full model so geo fields (address, city, latitude, longitude)
     * can be copied into the event row without an extra query.
     * Returns null when the location cannot be matched (blocks import — required FK).
     */
    private function resolveLocationRecord(Collection $row, int $csvRowNum, string $eventId): ?EventLocation
    {
        $name        = trim((string) ($row['location']     ?? ''));
        $address     = trim((string) ($row['address']      ?? ''));
        $city        = trim((string) ($row['city']         ?? ''));
        $state       = trim((string) ($row['state']        ?? ''));
        $zip         = trim((string) ($row['zip']          ?? ''));

        if ($name === '') {
            $this->refNotMatched++;
            $this->missingLocation++;
            Log::warning('[EventsImport] Reference lookup failed', [
                'row' => $csvRowNum, 'event_id' => $eventId,
                'type' => 'Location', 'csv_value' => '', 'normalized_value' => '',
                'reason' => 'Location name is empty',
            ]);
            return null;
        }

        // Composite key matches LocationsImport::uniqueKey()
        $key = implode('|', [
            Str::lower($name),
            Str::lower($address),
            Str::lower($city),
            Str::lower($state),
            Str::lower($zip),
        ]);

        if (array_key_exists($key, $this->locationCache)) {
            // Cache stores the full record (or null on miss) — return directly.
            return $this->locationCache[$key];
        }

        // Match on the same fields LocationsImport used to import.
        // Select all columns needed for geo copy-across.
        $location = EventLocation::whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->whereRaw('LOWER(COALESCE(address, \'\')) = ?', [Str::lower($address)])
            ->whereRaw('LOWER(COALESCE(city, \'\')) = ?', [Str::lower($city)])
            ->whereRaw('LOWER(COALESCE(state, \'\')) = ?', [Str::lower($state)])
            ->whereRaw('LOWER(COALESCE(zip_code, \'\')) = ?', [Str::lower($zip)])
            ->first();

        // Fallback: match by name only (address may differ slightly)
        if ($location === null && $name !== '') {
            $location = EventLocation::whereRaw('LOWER(name) = ?', [Str::lower($name)])->first();
            if ($location !== null) {
                Log::info('[EventsImport] Location matched by name-only fallback', [
                    'row'      => $csvRowNum,
                    'event_id' => $eventId,
                    'name'     => $name,
                    'db_id'    => $location->id,
                ]);
            }
        }

        // Cache the full record (or null) — avoids re-querying for the same location.
        $this->locationCache[$key] = $location;

        if ($location === null) {
            $this->refNotMatched++;
            $this->missingLocation++;
            Log::warning('[EventsImport] Reference lookup failed', [
                'row'             => $csvRowNum,
                'event_id'        => $eventId,
                'type'            => 'Location',
                'csv_value'       => $name,
                'normalized_value'=> Str::lower($name),
                'reason'          => 'No matching event_location found',
                'address'         => $address,
                'city'            => $city,
                'state'           => $state,
                'zip'             => $zip,
            ]);
        }
        return $location;
    }

    private function resolveDistributor(string $name, int $row, string $eventId): ?int
    {
        if ($name === '') {
            $this->refNotMatched++;
            $this->missingDistributor++;
            Log::warning('[EventsImport] Reference lookup failed', [
                'row' => $row, 'event_id' => $eventId,
                'type' => 'Distributor', 'csv_value' => $name,
                'normalized_value' => '', 'reason' => 'CSV value is empty',
            ]);
            return null;
        }
        $key = $this->normalizeRefName($name);
        if (array_key_exists($key, $this->distributorCache)) {
            $id = $this->distributorCache[$key];
            if ($id === null) {
                $this->refNotMatched++;
                $this->missingDistributor++;
            }
            return $id;
        }
        $this->distributorCache[$key] = null;
        $this->refNotMatched++;
        $this->missingDistributor++;
        Log::warning('[EventsImport] Reference lookup failed', [
            'row' => $row, 'event_id' => $eventId,
            'type' => 'Distributor', 'csv_value' => $name,
            'normalized_value' => $key, 'reason' => 'No matching distributor found',
        ]);
        return null;
    }

    private function resolveDepartment(string $name, int $row, string $eventId): ?int
    {
        if ($name === '') {
            $this->refNotMatched++;
            $this->missingDepartment++;
            Log::warning('[EventsImport] Reference lookup failed', [
                'row' => $row, 'event_id' => $eventId,
                'type' => 'Department', 'csv_value' => $name,
                'normalized_value' => '', 'reason' => 'CSV value is empty',
            ]);
            return null;
        }
        $key = $this->normalizeRefName($name);
        if (array_key_exists($key, $this->departmentCache)) {
            $id = $this->departmentCache[$key];
            if ($id === null) {
                $this->refNotMatched++;
                $this->missingDepartment++;
            }
            return $id;
        }
        $this->departmentCache[$key] = null;
        $this->refNotMatched++;
        $this->missingDepartment++;
        Log::warning('[EventsImport] Reference lookup failed', [
            'row' => $row, 'event_id' => $eventId,
            'type' => 'Department', 'csv_value' => $name,
            'normalized_value' => $key, 'reason' => 'No matching department found',
        ]);
        return null;
    }

    private function resolveMarket(string $name, int $row, string $eventId): ?int
    {
        if ($name === '') {
            $this->refNotMatched++;
            $this->missingMarket++;
            Log::warning('[EventsImport] Reference lookup failed', [
                'row' => $row, 'event_id' => $eventId,
                'type' => 'Market', 'csv_value' => $name,
                'normalized_value' => '', 'reason' => 'CSV value is empty',
            ]);
            return null;
        }
        $key = $this->normalizeRefName($name);
        if (array_key_exists($key, $this->marketCache)) {
            $id = $this->marketCache[$key];
            if ($id === null) {
                $this->refNotMatched++;
                $this->missingMarket++;
            }
            return $id;
        }
        $this->marketCache[$key] = null;
        $this->refNotMatched++;
        $this->missingMarket++;
        Log::warning('[EventsImport] Reference lookup failed', [
            'row' => $row, 'event_id' => $eventId,
            'type' => 'Market', 'csv_value' => $name,
            'normalized_value' => $key, 'reason' => 'No matching market found',
        ]);
        return null;
    }

    /**
     * General reference name normalizer.
     * Handles: hyphens→spaces, en/em-dash→space, collapse whitespace, lowercase.
     * Used for: Brand, Department, Distributor, Market.
     */
    private function normalizeRefName(string $name): string
    {
        $name = Str::lower(trim($name));
        $name = str_replace(['-', '–', '—'], ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        return trim($name);
    }

    /**
     * Event Type normalizer — same strategy as normalizeRefName.
     * Kept as a named alias so call-sites remain readable.
     */
    private function normalizeEventTypeName(string $name): string
    {
        return $this->normalizeRefName($name);
    }

    private function resolveEventType(string $name, int $row, string $eventId): ?int
    {
        if ($name === '') {
            $this->refNotMatched++;
            $this->missingEventType++;
            Log::warning('[EventsImport] Reference lookup failed', [
                'row' => $row, 'event_id' => $eventId,
                'type' => 'Event Type', 'csv_value' => $name,
                'normalized_value' => '', 'reason' => 'CSV value is empty',
            ]);
            return null;
        }

        $key = $this->normalizeEventTypeName($name);

        if (array_key_exists($key, $this->eventTypeCache)) {
            $id = $this->eventTypeCache[$key];
            if ($id === null) {
                $this->refNotMatched++;
                $this->missingEventType++;
                Log::warning('[EventsImport] Reference lookup failed', [
                    'row' => $row, 'event_id' => $eventId,
                    'type' => 'Event Type', 'csv_value' => $name,
                    'normalized_value' => $key, 'reason' => 'No matching event type found',
                ]);
            }
            return $id;
        }

        // Not in cache — unknown value, store null to avoid re-querying
        $this->eventTypeCache[$key] = null;
        $this->refNotMatched++;
        $this->missingEventType++;
        Log::warning('[EventsImport] Reference lookup failed', [
            'row' => $row, 'event_id' => $eventId,
            'type' => 'Event Type', 'csv_value' => $name,
            'normalized_value' => $key, 'reason' => 'No matching event type found',
        ]);
        return null;
    }

    /**
     * Manager: lookup-only by name. Never creates a user.
     * Returns null if not found — never blocks import.
     */
    private function resolveManager(string $name, int $row, string $eventId): ?int
    {
        if ($name === '') {
            $this->managerMissing++;
            Log::info('[EventsImport] Manager field empty — skipped', ['row' => $row, 'event_id' => $eventId]);
            return null;
        }

        $key = Str::lower($name);
        if (array_key_exists($key, $this->managerCache)) {
            $id = $this->managerCache[$key];
            if ($id === null) {
                $this->managerMissing++;
            }
            return $id;
        }

        // Match by full name or first+last name. Role 6 = Manager (existing pattern).
        $manager = User::where('role_id', 6)
            ->where(function ($q) use ($name, $key) {
                $q->whereRaw('LOWER(name) = ?', [$key])
                  ->orWhereRaw('LOWER(CONCAT(COALESCE(first_name,\'\'), \' \', COALESCE(last_name,\'\'))) = ?', [$key]);
            })
            ->first();

        $id = $manager?->id;
        $this->managerCache[$key] = $id;

        if ($id === null) {
            $this->managerMissing++;
            Log::info('[EventsImport] Manager not matched — continuing without manager', [
                'row'      => $row,
                'event_id' => $eventId,
                'manager'  => $name,
            ]);
        }

        return $id;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Date/Time parsing
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Parse "Day" (e.g. "1/15/2025") + "Start"/"End" (e.g. "10:00 AM")
     * into a datetime string stored in the event's own timezone.
     *
     * The existing Event model's formatted-date/time accessors parse
     * start_time using the event's timezone, so storing the local time
     * (not UTC-converted) is the correct convention for this application.
     */
    private function parseDateTime(
        string $day,
        string $time,
        string $iana,
        int $row,
        string $eventId,
    ): ?string {
        if ($day === '' || $time === '') {
            Log::warning('[EventsImport] Missing day or time', [
                'row' => $row, 'event_id' => $eventId, 'day' => $day, 'time' => $time,
            ]);
            return null;
        }

        try {
            // Support "n/j/Y" (no leading zeros) and "m/d/Y" (with leading zeros)
            $date = null;
            foreach (['n/j/Y', 'm/d/Y', 'Y-m-d'] as $fmt) {
                try {
                    $date = Carbon::createFromFormat($fmt, $day);
                    break;
                } catch (\Exception) {
                    // try next format
                }
            }

            if ($date === null) {
                throw new \RuntimeException("Unrecognised date format: {$day}");
            }

            // Parse time: "10:00 AM", "2:30 PM", "10:00:00 AM"
            $timeParts = preg_split('/\s+/', trim($time), 2);
            $timePart  = $timeParts[0] ?? '';
            $ampm      = strtoupper($timeParts[1] ?? 'AM');

            [$h, $m] = array_pad(explode(':', $timePart), 2, '0');
            $h = (int) $h;
            $m = (int) $m;

            if ($ampm === 'PM' && $h !== 12) {
                $h += 12;
            } elseif ($ampm === 'AM' && $h === 12) {
                $h = 0;
            }

            $date->setTime($h, $m, 0);

            // Store as-is in the event timezone (matches existing app convention:
            // Carbon::parse($start_time, $iana) is used by all formatters).
            return $date->format('Y-m-d H:i:s');

        } catch (\Throwable $e) {
            Log::warning('[EventsImport] DateTime parse failed — storing null', [
                'row'      => $row,
                'event_id' => $eventId,
                'day'      => $day,
                'time'     => $time,
                'error'    => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    private function buildAddressNote(Collection $row): ?string
    {
        $parts = array_filter([
            trim((string) ($row['address']      ?? '')),
            trim((string) ($row['city']         ?? '')),
            trim((string) ($row['state']        ?? '')),
            trim((string) ($row['zip']          ?? '')),
            ($sn = trim((string) ($row['store_number'] ?? ''))) !== '' ? 'Store #' . $sn : '',
        ]);
        return $parts ? implode(', ', $parts) : null;
    }

    private function regionFromState(string $state): string
    {
        $southeast = ['GA', 'SC', 'TN', 'NC', 'FL', 'AL', 'MS', 'LA', 'AR', 'KY', 'VA', 'WV'];
        $midwest   = ['WI', 'IA', 'IL', 'IN', 'OH', 'MI', 'MN', 'MO', 'ND', 'SD', 'NE', 'KS'];

        $upper = strtoupper($state);
        if (in_array($upper, $southeast, true)) {
            return 'Southeast';
        }
        if (in_array($upper, $midwest, true)) {
            return 'Midwest';
        }
        return 'Midwest';
    }

    // ─────────────────────────────────────────────────────────────────────
    // Cache bootstrap — called at top of each chunk to handle Maatwebsite
    // serialise/deserialise resetting object state.
    // ─────────────────────────────────────────────────────────────────────

    private function ensureCachesLoaded(): void
    {
        // Always reload — after Maatwebsite deserialises between chunks the
        // cache arrays are empty again, so we must repopulate them.
        // Each load is O(n) but only done once per chunk, not per row.
        $this->brandCache = Brand::query()
            ->select('id', 'brand_name')
            ->get()
            ->mapWithKeys(fn ($b) => [$this->normalizeRefName((string) $b->brand_name) => $b->id])
            ->all();

        // Select all columns needed for geo copy-across (address, city, latitude, longitude).
        $this->locationCache = EventLocation::query()
            ->select('id', 'name', 'address', 'city', 'state', 'zip_code', 'latitude', 'longitude')
            ->get()
            ->mapWithKeys(fn ($l) => [
                implode('|', [
                    Str::lower(trim((string) $l->name)),
                    Str::lower(trim((string) $l->address)),
                    Str::lower(trim((string) $l->city)),
                    Str::lower(trim((string) $l->state)),
                    Str::lower(trim((string) $l->zip_code)),
                ]) => $l,  // store full record, not just id
            ])
            ->all();

        $this->distributorCache = EventDistributor::query()
            ->select('id', 'name')
            ->get()
            ->mapWithKeys(fn ($d) => [$this->normalizeRefName((string) $d->name) => $d->id])
            ->all();

        $this->departmentCache = EventDepartment::query()
            ->select('id', 'name')
            ->get()
            ->mapWithKeys(fn ($d) => [$this->normalizeRefName((string) $d->name) => $d->id])
            ->all();

        $this->marketCache = Market::query()
            ->select('id', 'name')
            ->get()
            ->mapWithKeys(fn ($m) => [$this->normalizeRefName((string) $m->name) => $m->id])
            ->all();

        $this->eventTypeCache = EventType::query()
            ->select('id', 'name')
            ->get()
            ->mapWithKeys(fn ($t) => [$this->normalizeEventTypeName((string) $t->name) => $t->id])
            ->all();

        // Manager cache: load role_id=6 users, keyed by lowercase full name
        $this->managerCache = User::query()
            ->select('id', 'name', 'first_name', 'last_name')
            ->where('role_id', 6)
            ->get()
            ->flatMap(function ($u) {
                $entries = [];
                if ($u->name) {
                    $entries[Str::lower(trim($u->name))] = $u->id;
                }
                $fullName = trim($u->first_name . ' ' . $u->last_name);
                if ($fullName !== '' && $fullName !== ' ') {
                    $entries[Str::lower($fullName)] = $u->id;
                }
                return $entries;
            })
            ->all();

        Log::info('[EventsImport] Reference caches loaded', [
            'brands'       => count($this->brandCache),
            'locations'    => count($this->locationCache),
            'distributors' => count($this->distributorCache),
            'departments'  => count($this->departmentCache),
            'markets'      => count($this->marketCache),
            'event_types'  => count($this->eventTypeCache),
            'managers'     => count($this->managerCache),
        ]);
    }
}
