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
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // BA or Manager who submitted
            $table->unsignedBigInteger('event_id')->nullable(); // Optional event tag
            $table->unsignedBigInteger('brand_id')->nullable();

            $table->date('expense_date');
            $table->string('brand_name')->nullable(); // If no brand_id
            $table->decimal('amount', 10, 2);
            $table->text('reason');
            $table->string('receipt_path')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->string('dwolla_transfer_id')->nullable()->unique();
            $table->string('quickbooks_expense_id')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
