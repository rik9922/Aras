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
        Schema::table('events', function (Blueprint $table) {
            $table->string('recap_status')->nullable()->after('recap_needed');
            $table->decimal('recap_payout_amount', 10, 2)->nullable()->after('recap_status');
        });

        Schema::create('recap_deduction_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->unsignedBigInteger('recap_id')->nullable();
            $table->unsignedBigInteger('ambassador_id')->nullable();
            $table->string('reason');
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('recaps', function (Blueprint $table) {
            $table->dropColumn(['status', 'auto_deducted_at', 'auto_deduction_reason']);
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['recap_status', 'recap_payout_amount']);
        });

        Schema::dropIfExists('recap_deduction_logs');
    }
};

