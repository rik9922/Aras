<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batch_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('batch_id')
                ->constrained('payroll_batches')
                ->cascadeOnDelete();

            $table->enum('payment_type', [
                'recap',
                'reimbursement',
            ]);

            // Stores recap_id or reimbursement_id depending on payment_type
            $table->unsignedBigInteger('reference_id');

            $table->foreignId('ambassador_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->decimal('amount', 12, 2);

            $table->boolean('is_expedited')->default(false);

            $table->enum('status', [
                'created',
                'processing',
                'completed',
                'failed',
                'removed',
            ])->default('created');

            $table->string('dwolla_transaction_id')->nullable();

            $table->dateTime('processed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_batch_items');
    }
};
