<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ambassador_payouts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('event_id');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('reference')->unique();

            $table->foreignId('dwolla_funding_source_id')
                ->constrained('dwolla_funding_sources');

            $table->string('dwolla_transfer_id')->nullable()->unique();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            $table->decimal('amount', 10, 2);
            $table->string('currency')->default('USD');

            $table->enum('status', [
                'approved',
                'queued',
                'processing',
                'completed',
                'failed',
                'job_failed',
                'cancelled'
            ])->default('approved');

            $table->text('failure_reason')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->integer('retry_count')->default(0);
            $table->timestamp('last_retry_at')->nullable();

            $table->timestamps();

            $table->unique(['event_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambassador_payouts');
    }
};
