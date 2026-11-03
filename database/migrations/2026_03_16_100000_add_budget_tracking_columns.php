<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('budget_program_name')->nullable()->after('contract_recieved');
            $table->decimal('budget_max_amount', 14, 2)->nullable()->after('budget_program_name');
            $table->unsignedInteger('budget_max_events')->nullable()->after('budget_max_amount');
            $table->unsignedTinyInteger('budget_warn_percent')->default(80)->after('budget_max_events');
            $table->boolean('budget_enforce')->default(false)->after('budget_warn_percent');
            $table->boolean('budget_require_approval')->default(false)->after('budget_enforce');
            $table->string('budget_alert_email')->nullable()->after('budget_require_approval');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->string('budget_alert_email')->nullable()->after('notes');
        });

        // Columns appended without `after()` so this migration can run before/after QB columns safely.
        Schema::table('events', function (Blueprint $table) {
            $table->string('budget_approval_status', 32)->nullable();
            $table->timestamp('budget_approval_requested_at')->nullable();
            $table->foreignId('budget_approval_resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('budget_approval_resolved_at')->nullable();
            $table->text('budget_approval_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropForeign(['budget_approval_resolved_by']);
            $table->dropColumn([
                'budget_approval_status',
                'budget_approval_requested_at',
                'budget_approval_resolved_by',
                'budget_approval_resolved_at',
                'budget_approval_note',
            ]);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn('budget_alert_email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'budget_program_name',
                'budget_max_amount',
                'budget_max_events',
                'budget_warn_percent',
                'budget_enforce',
                'budget_require_approval',
                'budget_alert_email',
            ]);
        });
    }
};
