<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('recaps', 'is_draft')) {
            Schema::table('recaps', function (Blueprint $table) {
                $table->boolean('is_draft')->default(false)->after('is_approved');
            });
        }

        // MySQL index length safety: `ambassador_id` is VARCHAR(255) in this schema,
        // so we use prefix indexes to stay under older MySQL key length limits.
        $existingIndexes = collect(DB::select('SHOW INDEX FROM recaps'))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        if (! in_array('recaps_ambassador_id_is_draft_index', $existingIndexes, true)) {
            DB::statement('CREATE INDEX recaps_ambassador_id_is_draft_index ON recaps (ambassador_id(150), is_draft)');
        }

        if (! in_array('recaps_event_id_is_draft_index', $existingIndexes, true)) {
            DB::statement('CREATE INDEX recaps_event_id_is_draft_index ON recaps (event_id(150), is_draft)');
        }
    }

    public function down(): void
    {
        // Drop indexes first (then column).
        $existingIndexes = collect(DB::select('SHOW INDEX FROM recaps'))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        if (in_array('recaps_ambassador_id_is_draft_index', $existingIndexes, true)) {
            DB::statement('DROP INDEX recaps_ambassador_id_is_draft_index ON recaps');
        }

        if (in_array('recaps_event_id_is_draft_index', $existingIndexes, true)) {
            DB::statement('DROP INDEX recaps_event_id_is_draft_index ON recaps');
        }

        if (Schema::hasColumn('recaps', 'is_draft')) {
            Schema::table('recaps', function (Blueprint $table) {
                $table->dropColumn('is_draft');
            });
        }
    }
};

