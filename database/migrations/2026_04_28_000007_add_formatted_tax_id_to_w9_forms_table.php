<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('w9_forms', function (Blueprint $table) {
            if (! Schema::hasColumn('w9_forms', 'formatted_tax_id')) {
                // EIN formatted as "12-3456789", SSN stored as 9 digits.
                $table->string('formatted_tax_id', 16)->nullable()->after('ssn_ein');
            }
        });
    }

    public function down(): void
    {
        Schema::table('w9_forms', function (Blueprint $table) {
            if (Schema::hasColumn('w9_forms', 'formatted_tax_id')) {
                $table->dropColumn('formatted_tax_id');
            }
        });
    }
};

