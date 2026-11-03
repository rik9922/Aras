<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assign_ambassadors', function (Blueprint $table) {
            if (! Schema::hasColumn('assign_ambassadors', 'pinata_amount')) {
                $table->unsignedTinyInteger('pinata_amount')->default(0)->after('override_pay');
            }
            if (! Schema::hasColumn('assign_ambassadors', 'is_no_show')) {
                $table->boolean('is_no_show')->default(false)->after('pinata_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assign_ambassadors', function (Blueprint $table) {
            if (Schema::hasColumn('assign_ambassadors', 'is_no_show')) {
                $table->dropColumn('is_no_show');
            }
        });
    }
};
