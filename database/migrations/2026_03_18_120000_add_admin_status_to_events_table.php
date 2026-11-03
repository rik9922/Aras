<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('admin_status')->nullable()->after('event_status');
            $table->unsignedBigInteger('admin_status_set_by')->nullable()->after('admin_status');
            $table->timestamp('admin_status_set_at')->nullable()->after('admin_status_set_by');

            $table->string('qb_publish_status')->nullable()->after('admin_status_set_at'); // success|failed
            $table->text('qb_publish_error')->nullable()->after('qb_publish_status');
            $table->timestamp('qb_published_at')->nullable()->after('qb_publish_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn([
                'admin_status',
                'admin_status_set_by',
                'admin_status_set_at',
                'qb_publish_status',
                'qb_publish_error',
                'qb_published_at',
            ]);
        });
    }
};

