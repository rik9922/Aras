<?php

namespace App\Exports\Concerns;

use App\Services\SpreadsheetCellCache;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeExport;
use PhpOffice\PhpSpreadsheet\Settings;

trait UsesTemporaryCellCache
{
    public function registerEvents(): array
    {
        $previous = null;
        return [
            BeforeExport::class => static function ($event) use (&$previous) {
                // XLSX still builds a shared string table and worksheet XML buffer.
                // Bound the extra allowance for large archives without removing PHP's limit.
                $limit = trim((string) ini_get('memory_limit'));
                $bytes = (int) $limit * match (strtolower(substr($limit, -1))) {
                    'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1,
                };
                if ($limit !== '-1' && $bytes < 268435456 && $event->getConcernable()->query()->count() > 5000) {
                    ini_set('memory_limit', '256M');
                }
                $previous = Settings::getCache();
                if (extension_loaded('pdo_sqlite')) {
                    Settings::setCache(new SpreadsheetCellCache());
                }
            },
            // Each sheet retains its cache instance for writing. Restore the global
            // default so unrelated spreadsheets in this process are unaffected.
            AfterSheet::class => static function () use (&$previous) {
                if ($previous !== null) Settings::setCache($previous);
            },
        ];
    }
}
