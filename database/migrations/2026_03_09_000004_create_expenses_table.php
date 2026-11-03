<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('qb_expense_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('payout_id')->nullable();
            $table->unsignedBigInteger('reimbursement_id')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_id')->nullable();
            $table->date('txn_date')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency_code', 10)->default('USD');
            $table->text('private_note')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('created_time')->nullable();
            $table->timestamp('last_updated_time')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

