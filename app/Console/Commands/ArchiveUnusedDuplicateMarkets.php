<?php

namespace App\Console\Commands;

use App\Models\Market;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ArchiveUnusedDuplicateMarkets extends Command
{
    protected $signature = 'markets:archive-unused-duplicates {--apply : Soft-delete duplicates with no references} {--id=* : Restrict the review or archive to specific market IDs}';
    protected $description = 'Review legacy markets with matching On/Off Premise markets; archive only unreferenced duplicates';

    public function handle(): int
    {
        $normalize = static fn ($name) => preg_replace('/[^a-z0-9]/', '', strtolower($name));
        $markets = Market::orderBy('id')->get(['id', 'name']);
        $typed = [];
        foreach ($markets as $market) {
            if (preg_match('/\s+(?:off|on)[ -]?premise\s*$/i', $market->name)) {
                $base = preg_replace('/\s+(?:off|on)[ -]?premise\s*$/i', '', $market->name);
                $typed[$normalize($base)][] = $market->name;
            }
        }

        // Include historical rows, pivots, and comma/JSON lists on user records.
        $columns = DB::select("SELECT TABLE_NAME AS tbl, COLUMN_NAME AS col FROM information_schema.columns WHERE table_schema = DATABASE() AND COLUMN_NAME IN ('market_id', 'market_ids')");
        $selectedIds = array_map('intval', $this->option('id'));
        $rows = [];
        $archived = 0;
        foreach ($markets as $market) {
            if ($selectedIds && !in_array((int) $market->id, $selectedIds, true)) continue;
            $matches = $typed[$normalize($market->name)] ?? [];
            if (!$matches) continue;
            DB::transaction(function () use ($market, $matches, $columns, &$rows, &$archived) {
                $current = Market::whereKey($market->id)->lockForUpdate()->first();
                if (!$current) return;
                $references = 0;
                foreach ($columns as $column) {
                    $references += DB::table($column->tbl)
                        ->whereRaw('CAST(`'.$column->col.'` AS CHAR) REGEXP ?', ['(^|[^0-9])'.$market->id.'([^0-9]|$)'])->count();
                }
                $status = $references ? 'Keep: linked records' : 'Unused duplicate';
                if ($this->option('apply') && $references === 0) {
                    $current->delete();
                    $archived++;
                    $status = 'Archived';
                }
                $rows[] = [$market->id, $market->name, implode('; ', $matches), $references, $status];
            });
        }
        $this->table(['ID', 'Legacy market', 'On/Off Premise matches', 'References', 'Action'], $rows);
        $this->info($this->option('apply') ? "Archived {$archived} unused duplicate markets. Linked markets were preserved." : 'Preview only. Add --apply to archive unused duplicates.');
        return self::SUCCESS;
    }
}
