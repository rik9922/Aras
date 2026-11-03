<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quickbooks_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('qb_account_id')->unique()->comment('QuickBooks Account ID');
            $table->string('name');
            $table->string('account_type')->nullable()->comment('e.g. Income, Expense, Bank, CreditCard');
            $table->string('account_sub_type')->nullable()->comment('e.g. ServiceFeeIncome, CostOfLabor');
            $table->boolean('is_active')->default(true);
            $table->string('sync_token')->nullable();
            $table->string('currency_ref')->nullable();
            $table->timestamps();

            $table->index('account_type');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quickbooks_accounts');
    }
};
