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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained('events')->onDelete('set null'); // Link to events table
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Track creator
            $table->string('qb_invoice_id')->unique();
            $table->string('doc_number')->nullable();
            $table->string('customer_name')->nullable(); // Made nullable for flexibility
            $table->string('customer_id')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('balance', 10, 2)->default(0);
            $table->date('due_date')->nullable();
            $table->date('invoice_date')->default(now()); // Default to today
            $table->string('status')->default('Draft');
            $table->string('currency_code', 3)->default('USD');
            $table->json('line_items')->nullable();
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();
            $table->string('email_status')->nullable();
            $table->string('print_status')->nullable();
            $table->text('private_note')->nullable();
            $table->text('customer_memo')->nullable();
            $table->string('sync_token')->nullable();
            $table->string('payment_type')->nullable(); // e.g., Select from event form
            $table->boolean('receipt_required')->default(false); // Mirror event form
            $table->timestamp('created_time')->nullable();
            $table->timestamp('last_updated_time')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('event_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
