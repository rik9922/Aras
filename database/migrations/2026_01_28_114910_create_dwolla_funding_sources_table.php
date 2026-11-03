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
        Schema::create('dwolla_funding_sources', function (Blueprint $table) {
            $table->id();

            $table->foreignId('dwolla_customer_id')
                ->constrained('dwolla_customers')
                ->cascadeOnDelete();

            $table->string('dwolla_funding_source_id')->unique();

            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_type')->nullable();
            $table->string('status')->nullable();

            $table->string('microdeposits_status')->nullable();
            $table->timestamp('microdeposits_initiated_at')->nullable();
            $table->timestamp('microdeposits_completed_at')->nullable();

            $table->boolean('is_default')->default(false);
            $table->timestamp('verified_at')->nullable();

            $table->json('raw_response')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dwolla_funding_sources');
    }
};
