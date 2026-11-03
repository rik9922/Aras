<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('recaps', 'timing_successful')) {
            Schema::table('recaps', function (Blueprint $table) {
                $table->boolean('timing_successful')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('recaps', fn (Blueprint $table) => $table->dropColumn('timing_successful'));
    }
};
