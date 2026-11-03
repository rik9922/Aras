<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('w9_forms', function (Blueprint $table) {
            $table->string('business_name_ein')->nullable()->after('business_name');
        });
    }

    public function down(): void
    {
        Schema::table('w9_forms', function (Blueprint $table) {
            $table->dropColumn('business_name_ein');
        });
    }
};
