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
        Schema::create('dwolla_webhook_events', function (Blueprint $table) {
            $table->id();

            $table->string('event_id')->nullable()->index();
            $table->string('topic')->index();

            $table->string('resource_id')->nullable();
            $table->string('resource_type')->nullable();

            // Security & debugging
            $table->string('signature')->nullable();
            $table->json('headers')->nullable();

            $table->json('payload');

            $table->enum('status', [
                'received',
                'processed',
                'failed',
                'ignored',
            ])->default('received');

            $table->string('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->foreignId('ambassador_payout_id')->nullable()
                ->constrained('ambassador_payouts')
                ->nullOnDelete();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dwolla_webhook_events');
    }
};
