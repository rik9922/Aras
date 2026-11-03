<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // event_payout, reimbursement
            $table->unsignedBigInteger('source_id'); // ambassador_payout_id or reimbursement_id
            $table->string('source_type', 50); // AmbassadorPayout, Reimbursement
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('status', 50)->index();
            $table->string('dwolla_transfer_id')->nullable()->index();
            $table->string('description')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_transactions');
    }
};
