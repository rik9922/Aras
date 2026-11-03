<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Fix any existing NULL or duplicate doc_numbers before adding
        //        the unique constraint so the migration never fails on live data.

        // Assign placeholder numbers to NULLs
        $nulls = DB::table('invoices')->whereNull('doc_number')->orderBy('id')->get(['id']);
        foreach ($nulls as $row) {
            DB::table('invoices')->where('id', $row->id)->update([
                'doc_number' => 'INV-LEGACY-' . $row->id,
            ]);
        }

        // Deduplicate: keep the first occurrence of each doc_number,
        // append -DUP-{id} to the rest.
        $duplicates = DB::table('invoices')
            ->select('doc_number', DB::raw('MIN(id) as first_id'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('doc_number')
            ->having('cnt', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $others = DB::table('invoices')
                ->where('doc_number', $dup->doc_number)
                ->where('id', '!=', $dup->first_id)
                ->orderBy('id')
                ->get(['id']);

            foreach ($others as $row) {
                DB::table('invoices')->where('id', $row->id)->update([
                    'doc_number' => $dup->doc_number . '-DUP-' . $row->id,
                ]);
            }
        }

        // ── 2. Now safe to add the unique index
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('doc_number')->nullable(false)->change();
            $table->unique('doc_number', 'invoices_doc_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_doc_number_unique');
            $table->string('doc_number')->nullable()->change();
        });
    }
};
