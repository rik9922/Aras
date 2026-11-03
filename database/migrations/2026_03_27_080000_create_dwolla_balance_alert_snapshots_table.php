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
        Schema::create('dwolla_balance_alert_snapshots', function (Blueprint $table) {
            $table->id();
            $table->dateTime('window_start');
            $table->dateTime('window_end');
            $table->unsignedInteger('forecast_days');
            $table->decimal('forecast_total', 12, 2)->default(0);
            $table->decimal('buffer_amount', 12, 2)->default(0);
            $table->decimal('fixed_floor_amount', 12, 2)->nullable();
            $table->decimal('required_minimum', 12, 2)->default(0);
            $table->decimal('operating_balance', 12, 2)->default(0);
            $table->decimal('shortfall', 12, 2)->default(0);
            $table->string('currency', 10)->default('USD');
            $table->boolean('is_breach')->default(false);
            $table->string('severity', 20)->nullable();
            $table->dateTime('notified_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['is_breach', 'severity']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dwolla_balance_alert_snapshots');
    }
};
