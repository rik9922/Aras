<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'qb_customer_sync_hash')) {
                $table->string('qb_customer_sync_hash', 64)->nullable()->after('qb_customer_last_sync_error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'qb_customer_sync_hash')) {
                $table->dropColumn('qb_customer_sync_hash');
            }
        });
    }
};
