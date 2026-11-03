<?php

namespace App\Imports;

use App\Models\Market;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Imports Markets from a CSV with a "Market" column header.
 *
 * Maatwebsite WithHeadingRow slugifies headings:
 *   "Market" → key 'market'
 *
 * Uniqueness: name (case-insensitive).
 * WithChunkReading intentionally omitted — serialization resets counters.
 */
class MarketsImport implements ToCollection, WithHeadingRow
{
    public int $imported     = 0;
    public int $skipped      = 0;
    public int $empty        = 0;
    public int $insertFailed = 0;

    public function collection(Collection $rows): void
    {
        Log::info('[MarketsImport] STAGE 7a — collection() called', [
            'total_rows'       => $rows->count(),
            'first_row_keys'   => $rows->isNotEmpty() ? array_keys($rows->first()->toArray()) : [],
            'first_row_sample' => $rows->isNotEmpty() ? $rows->first()->toArray() : [],
        ]);

        if ($rows->isEmpty()) {
            Log::warning('[MarketsImport] STAGE 7a — collection is empty');
            return;
        }

        // ── Extract market names ───────────────────────────────────────────
        $names = $rows
            ->map(function ($row) {
                $val = trim((string) ($row['market'] ?? ''));
                if ($val === '') {
                    $this->empty++;
                }
                return $val;
            })
            ->filter(fn ($name) => $name !== '')
            ->values();

        Log::info('[MarketsImport] STAGE 7b — Names extracted', [
            'total_rows'      => $rows->count(),
            'empty_rows'      => $this->empty,
            'non_empty_names' => $names->count(),
            'sample_names'    => $names->take(5)->values()->toArray(),
        ]);

        if ($names->isEmpty()) {
            Log::warning('[MarketsImport] STAGE 7b — All rows were empty');
            return;
        }

        // ── Load existing market names for dedup (one query) ──────────────
        try {
            $existing = Market::query()
                ->pluck('name')
                ->map(fn ($n) => Str::lower(trim((string) $n)))
                ->flip()
                ->all();

            Log::info('[MarketsImport] STAGE 7c — Existing markets loaded', [
                'existing_count' => count($existing),
            ]);
        } catch (\Throwable $e) {
            Log::error('[MarketsImport] STAGE 7c — DB query failed', [
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

            try {
                Market::create([
                    'name'   => $name,
                    'status' => Market::STATUS_ACTIVE,
                ]);
                $existing[$normalised] = true;
                $this->imported++;
            } catch (\Throwable $e) {
                $this->insertFailed++;
                Log::error('[MarketsImport] STAGE 7d — create() failed', [
                    'name'            => $name,
                    'exception_class' => get_class($e),
                    'message'         => $e->getMessage(),
                    'file'            => $e->getFile(),
                    'line'            => $e->getLine(),
                ]);
            }
        }

        Log::info('[MarketsImport] STAGE 7d — Insert loop complete', [
            'imported'       => $this->imported,
            'skipped_total'  => $this->skipped,
            'intra_file_dup' => $intraFileDups,
            'db_dup'         => $dbDups,
            'insert_failed'  => $this->insertFailed,
            'empty_rows'     => $this->empty,
        ]);
    }
}
