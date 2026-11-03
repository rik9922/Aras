<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code', 50);

            // QuickBooks Payment Method
            $table->string('quickbooks_payment_method_id')->nullable()->index();

            // QuickBooks Account
            $table->string('quickbooks_account_id')->nullable()->index();

            $table->string('account_type', 50)->nullable(); 
            $table->string('detail_type', 100)->nullable(); 

            $table->decimal('opening_balance', 15, 2)->nullable();
            $table->date('as_of_date')->nullable();

            $table->boolean('is_default')->default(false);
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};