<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Make manager_id nullable on the events table.
 *
 * The Event CSV import does not always include a Manager, and a missing or
 * unmatched manager must never block the import. This migration drops the
 * NOT NULL constraint so manager_id can be stored as NULL.
 *
 * client_id is already nullable — confirmed in 2026_07_17_102929 migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Drop the existing FK before modifying the column
            $table->dropForeign(['manager_id']);

            // Re-declare as nullable with the same FK constraint
            $table->unsignedBigInteger('manager_id')->nullable()->change();

            $table->foreign('manager_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);

            // Revert: NOT NULL (fills any existing NULLs with 0 before reverting)
            DB::statement('UPDATE events SET manager_id = 0 WHERE manager_id IS NULL');

            $table->unsignedBigInteger('manager_id')->nullable(false)->change();

            $table->foreign('manager_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
