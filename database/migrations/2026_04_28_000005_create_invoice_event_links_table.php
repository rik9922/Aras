<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_event_links')) {
            return;
        }

        Schema::create('invoice_event_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();

            // Billing party (client) that this invoice line-set corresponds to.
            // Nullable for legacy/internals; in practice set to the invoiced client.
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['invoice_id', 'event_id', 'client_id'], 'iel_invoice_event_client_unique');
            $table->index(['event_id', 'client_id'], 'iel_event_client_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_event_links');
    }
};

