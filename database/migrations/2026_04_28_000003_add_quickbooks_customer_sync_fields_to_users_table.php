<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'qb_customer_sync_status')) {
                $table->string('qb_customer_sync_status', 32)->nullable()->after('quickbooks_customer_id');
            }
            if (! Schema::hasColumn('users', 'qb_customer_last_sync_attempt_at')) {
                $table->timestamp('qb_customer_last_sync_attempt_at')->nullable()->after('qb_customer_sync_status');
            }
            if (! Schema::hasColumn('users', 'qb_customer_synced_at')) {
                $table->timestamp('qb_customer_synced_at')->nullable()->after('qb_customer_last_sync_attempt_at');
            }
            if (! Schema::hasColumn('users', 'qb_customer_last_sync_error')) {
                $table->text('qb_customer_last_sync_error')->nullable()->after('qb_customer_synced_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'qb_customer_last_sync_error',
                'qb_customer_synced_at',
                'qb_customer_last_sync_attempt_at',
                'qb_customer_sync_status',
            ] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

