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
        Schema::create('event_billing_parties', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('client_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /**
             * Primary Billing Party.
             * Cup Charge always belongs to this party.
             */
            $table->boolean('is_primary')->default(false);

            /**
             * Display order in the UI.
             */
            $table->unsignedSmallInteger('sort_order')->default(0);

            /**
             * Snapshot of the client's labor rate.
             * This is copied from the client profile when the event is created
             * so future profile changes do not affect existing events.
             */
            $table->decimal('labor_rate', 12, 2)->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_billing_parties');
    }
};
