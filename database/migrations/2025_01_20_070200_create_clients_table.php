<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // ── Contract ────────────────────────────────────────────────────
            $table->integer('contract_recieved')->default(1);

            // ── Billing defaults ────────────────────────────────────────────
            $table->decimal('default_labor_amount', 12, 2)->nullable();
            $table->decimal('default_buyback_amount', 12, 2)->nullable();
            $table->decimal('default_cups_amount', 12, 2)->nullable();
            $table->unsignedInteger('default_receipt_percent')->nullable();

            // ── Address ─────────────────────────────────────────────────────
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 20)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('country', 100)->nullable()->default('USA');

            // ── Business profile ────────────────────────────────────────────
            $table->string('company_name')->nullable();
            $table->string('primary_billing_contact')->nullable();
            $table->text('notes')->nullable();

            // ── QuickBooks customer sync ─────────────────────────────────────
            $table->string('quickbooks_customer_id')->nullable();
            $table->string('qb_customer_sync_status', 32)->nullable();
            $table->timestamp('qb_customer_last_sync_attempt_at')->nullable();
            $table->timestamp('qb_customer_synced_at')->nullable();
            $table->text('qb_customer_last_sync_error')->nullable();

            // ── Budget tracking ──────────────────────────────────────────────
            $table->string('budget_program_name')->nullable();
            $table->decimal('budget_max_amount', 14, 2)->nullable();
            $table->unsignedInteger('budget_max_events')->nullable();
            $table->unsignedTinyInteger('budget_warn_percent')->default(80);
            $table->boolean('budget_enforce')->default(false);
            $table->boolean('budget_require_approval')->default(false);
            $table->string('budget_alert_email')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
