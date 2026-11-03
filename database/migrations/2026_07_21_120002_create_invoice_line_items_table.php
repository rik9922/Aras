<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->id();

            // Parent invoice
            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();

            // Service reference — nullable for manual/custom line items
            $table->foreignId('service_id')
                ->nullable()
                ->constrained('services')
                ->nullOnDelete();

            // Line item content
            $table->string('description')->comment('Line item description; auto-filled from service or manual entry');
            $table->decimal('quantity', 10, 4)->default(1)->comment('Qty or hours');
            $table->decimal('rate', 10, 2)->default(0)->comment('Unit price / rate');
            $table->decimal('amount', 10, 2)->default(0)->comment('quantity × rate');

            // Display ordering
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['invoice_id', 'sort_order'], 'ili_invoice_sort_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_line_items');
    }
};
