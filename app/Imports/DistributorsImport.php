<?php

namespace App\Imports;

use App\Models\EventDistributor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports distributors from a CSV with a "Distributor" column header.
 *
 * Maatwebsite WithHeadingRow slugifies headings:
 *   "Distributor" → key 'distributor'
 *
 * WithChunkReading is intentionally NOT used — it serializes/unserializes
 * the import object between chunks, resetting $imported/$skipped to 0.
 */
class DistributorsImport implements ToCollection, WithHeadingRow
{
    /** Total new distributors inserted this run. */
    public int $imported = 0;

    /** Total rows skipped (already existed or duplicate in file). */
    public int $skipped = 0;

    /** Total empty rows ignored. */
    public int $empty = 0;

    public function collection(Collection $rows): void
    {
        Log::info('[DistributorsImport] STAGE 7a — collection() called', [
            'total_rows'       => $rows->count(),
            'first_row_keys'   => $rows->isNotEmpty() ? array_keys($rows->first()->toArray()) : [],
            'first_row_sample' => $rows->isNotEmpty() ? $rows->first()->toArray() : [],
        ]);

        if ($rows->isEmpty()) {
            Log::warning('[DistributorsImport] STAGE 7a — collection is empty');
            return;
        }

        // ── Extract distributor names ──────────────────────────────────────
        // WithHeadingRow key: "Distributor" → 'distributor'
        $names = $rows
            ->map(function ($row) {
                $val = trim((string) ($row['distributor'] ?? ''));
                if ($val === '') {
                    $this->empty++;
                }
                return $val;
            })
            ->filter(fn ($name) => $name !== '')
            ->values();

        Log::info('[DistributorsImport] STAGE 7b — Names extracted', [
            'total_rows'      => $rows->count(),
            'empty_rows'      => $this->empty,
            'non_empty_names' => $names->count(),
            'sample_names'    => $names->take(5)->values()->toArray(),
        ]);

        if ($names->isEmpty()) {
            Log::warning('[DistributorsImport] STAGE 7b — All rows were empty');
            return;
        }

        // ── Load existing distributor names for dedup ─────────────────────
        try {
            $existing = EventDistributor::query()
                ->pluck('name')
                ->map(fn ($n) => Str::lower(trim((string) $n)))
                ->flip()
                ->all();

            Log::info('[DistributorsImport] STAGE 7c — Existing distributors loaded', [
                'existing_count' => count($existing),
            ]);
        } catch (\Throwable $e) {
            Log::error('[DistributorsImport] STAGE 7c — DB query failed', [
                'exception_class' => get_class($e),
                'message'         => $e->getMessage(),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
            ]);
            throw $e;
        }

        // ── Insert loop ────────────────────────────────────────────────────
        $seenThisRun   = [];
        $intraFileDups = 0;
        $dbDups        = 0;
        $insertFailed  = 0;

        foreach ($names as $name) {
            $normalised = Str::lower($name);

            if (isset($seenThisRun[$normalised])) {
                $this->skipped++;
                $intraFileDups++;
                continue;
            }

            $seenThisRun[$normalised] = true;

            if (isset($existing[$normalised])) {
                $this->skipped++;
                $dbDups++;
                continue;
            }

            try {
                EventDistributor::create([
                    'name'             => $name,
                    'company_name'     => $name,
                    'distributor_type' => 'Imported',
                    'status'           => '1',
                ]);
                $existing[$normalised] = true;
                $this->imported++;
            } catch (\Throwable $e) {
                $insertFailed++;
                Log::error('[DistributorsImport] STAGE 7d — create() failed', [
                    'name'            => $name,
                    'exception_class' => get_class($e),
                    'message'         => $e->getMessage(),
                    'file'            => $e->getFile(),
                    'line'            => $e->getLine(),
                ]);
            }
        }

        Log::info('[DistributorsImport] STAGE 7d — Insert loop complete', [
            'imported'       => $this->imported,
            'skipped_total'  => $this->skipped,
            'intra_file_dup' => $intraFileDups,
            'db_dup'         => $dbDups,
            'insert_failed'  => $insertFailed,
            'empty_rows'     => $this->empty,
        ]);
    }
}
