<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bug 2 (MC cmon1a7u4005d3r1h5cdct2vh): the recap form's State dropdown was
 * empty because the `states` table didn't exist on production. The State
 * model and `getState()` helper expected a `states` table with `id`, `name`,
 * and `short_name` columns (referenced by EventLocation, User, Recap,
 * Ambassador forms, and the Dwolla service `getStateShortName`).
 *
 * This migration creates the table only if it doesn't already exist (to be
 * safe on environments where someone may have already created it ad-hoc).
 * If a pre-existing table uses `abbreviation` instead of `short_name`, the
 * column is renamed so the rest of the codebase keeps working.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('states')) {
            // Backfill the expected `short_name` column on a pre-existing table
            // without dropping data.
            Schema::table('states', function (Blueprint $table) {
                if (!Schema::hasColumn('states', 'short_name')) {
                    $table->string('short_name', 8)->nullable()->after('name');
                }
            });

            // If a pre-existing table used `abbreviation`, copy values into
            // `short_name` so the rest of the app keeps working.
            if (Schema::hasColumn('states', 'abbreviation') && Schema::hasColumn('states', 'short_name')) {
                DB::table('states')
                    ->whereNull('short_name')
                    ->update(['short_name' => DB::raw('abbreviation')]);
            }

            return;
        }

        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('short_name', 8)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Intentionally do not drop the table on rollback — losing seeded
        // states would cascade-break every form that references state_id.
    }
};
