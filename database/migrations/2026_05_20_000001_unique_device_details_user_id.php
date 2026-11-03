<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('device_details')) {
            return;
        }

        $this->deduplicateRows();

        Schema::table('device_details', function (Blueprint $table) {
            if (! $this->indexExists('device_details', 'device_details_user_id_unique')) {
                $table->unique('user_id', 'device_details_user_id_unique');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('device_details')) {
            return;
        }

        Schema::table('device_details', function (Blueprint $table) {
            if ($this->indexExists('device_details', 'device_details_user_id_unique')) {
                $table->dropUnique('device_details_user_id_unique');
            }
        });
    }

    private function deduplicateRows(): void
    {
        $duplicateUserIds = DB::table('device_details')
            ->select('user_id')
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('user_id');

        foreach ($duplicateUserIds as $userId) {
            $keepId = DB::table('device_details')
                ->where('user_id', $userId)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->value('id');

            if ($keepId === null) {
                continue;
            }

            DB::table('device_details')
                ->where('user_id', $userId)
                ->where('id', '!=', $keepId)
                ->delete();
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($index) => ($index->name ?? '') === $indexName);
        }

        $database = $connection->getDatabaseName();

        $result = DB::select(
            'SELECT COUNT(*) AS aggregate FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $indexName]
        );

        return (int) ($result[0]->aggregate ?? 0) > 0;
    }
};
