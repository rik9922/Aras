<?php

namespace App\Imports;

use App\Models\Brand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports brands from a CSV with a "Brand" or "Brands" column header.
 *
 * WithChunkReading is intentionally NOT used — Maatwebsite serializes/
 * unserializes the import object between chunks, resetting $imported and
 * $skipped to 0 on every chunk boundary.
 */
class BrandsImport implements ToCollection, WithHeadingRow
{
    /** Total new brands inserted this run. */
    public int $imported = 0;

    /** Total rows skipped (already existed or duplicate in file). */
    public int $skipped = 0;

    public function collection(Collection $rows): void
    {
        // ── STAGE 7a: What Maatwebsite handed us ───────────────────────────
        Log::info('[BrandsImport] STAGE 7a — collection() called', [
            'total_rows'         => $rows->count(),
            'first_row_keys'     => $rows->isNotEmpty() ? array_keys($rows->first()->toArray()) : [],
            'first_row_sample'   => $rows->isNotEmpty() ? $rows->first()->toArray() : [],
        ]);

        if ($rows->isEmpty()) {
            Log::warning('[BrandsImport] STAGE 7a — collection is empty, nothing to import');
            return;
        }

        // ── STAGE 7b: Extract brand names ──────────────────────────────────
        // Maatwebsite WithHeadingRow slugifies headings:
        //   "Brand"  → key 'brand'
        //   "Brands" → key 'brands'
        // We try both so either spelling works.
        $emptyCount = 0;
        $names = $rows
            ->map(function ($row) use (&$emptyCount) {
                $val = trim((string) ($row['brand'] ?? ''));
                if ($val === '') {
                    $val = trim((string) ($row['brands'] ?? ''));
                }
                if ($val === '') {
                    $emptyCount++;
                }
                return $val;
            })
            ->filter(fn ($name) => $name !== '')
            ->values();

        Log::info('[BrandsImport] STAGE 7b — Names extracted from CSV', [
            'total_rows'        => $rows->count(),
            'empty_rows'        => $emptyCount,
            'non_empty_names'   => $names->count(),
            'sample_names'      => $names->take(5)->values()->toArray(),
        ]);

        if ($names->isEmpty()) {
            Log::warning('[BrandsImport] STAGE 7b — All rows were empty after extraction');
            return;
        }

        // ── STAGE 7c: Load existing brands for dedup ───────────────────────
        try {
            $existing = Brand::query()
                ->pluck('brand_name')
                ->map(fn ($n) => Str::lower(trim((string) $n)))
                ->flip()
                ->all();

            Log::info('[BrandsImport] STAGE 7c — Existing brands loaded', [
                'existing_count' => count($existing),
            ]);
        } catch (\Throwable $e) {
            Log::error('[BrandsImport] STAGE 7c — DB query for existing brands failed', [
                'exception_class' => get_class($e),
                'message'         => $e->getMessage(),
                'file'            => $e->getFile(),
                'line'            => $e->getLine(),
            ]);
            throw $e;
        }

        // ── STAGE 7d: Insert loop ──────────────────────────────────────────
        $seenThisRun    = [];
        $intraFileDups  = 0;
        $dbDups         = 0;
        $insertFailed   = 0;

        foreach ($names as $name) {
            $normalised = Str::lower($name);

            // Intra-file duplicate
            if (isset($seenThisRun[$normalised])) {
                $this->skipped++;
                $intraFileDups++;
                continue;
            }

            $seenThisRun[$normalised] = true;

            // Already in DB
            if (isset($existing[$normalised])) {
                $this->skipped++;
                $dbDups++;
                continue;
            }

            // Insert
            try {
                Brand::create([
                    'brand_name' => $name,
                    'status'     => 1,
                ]);
                $existing[$normalised] = true;
                $this->imported++;
            } catch (\Throwable $e) {
                $insertFailed++;
                Log::error('[BrandsImport] STAGE 7d — Brand::create() failed', [
                    'brand_name'      => $name,
                    'exception_class' => get_class($e),
                    'message'         => $e->getMessage(),
                    'file'            => $e->getFile(),
                    'line'            => $e->getLine(),
                    'trace'           => $e->getTraceAsString(),
                ]);
                // Continue processing remaining rows rather than aborting
            }
        }

        Log::info('[BrandsImport] STAGE 7d — Insert loop complete', [
            'imported'       => $this->imported,
            'skipped_total'  => $this->skipped,
            'intra_file_dup' => $intraFileDups,
            'db_dup'         => $dbDups,
            'insert_failed'  => $insertFailed,
        ]);
    }
}
