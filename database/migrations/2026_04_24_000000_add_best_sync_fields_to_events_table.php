<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('best_synced_at')->nullable()->after('integrate_with_best');
            $table->enum('best_status', ['pending', 'processing', 'success', 'failed'])->default('pending')->after('best_synced_at');
            $table->longText('best_response')->nullable()->after('best_status');
            $table->longText('best_payload')->nullable()->after('best_response');

            $table->index('best_synced_at');
            $table->index('best_status');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['best_synced_at']);
            $table->dropIndex(['best_status']);
            $table->dropColumn([
                'best_synced_at',
                'best_status',
                'best_response',
                'best_payload',
            ]);
        });
    }
};

