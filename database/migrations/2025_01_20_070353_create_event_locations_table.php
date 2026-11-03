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
        Schema::create('event_locations', function (Blueprint $table) {
            $table->id();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('zip_code', 6)->nullable();
            $table->string('latitude', 100)->nullable();
            $table->string('longitude', 100)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->string('timezone_label', 100)->nullable();
            $table->string('internal_notes')->nullable();
            $table->string('name', 100)->nullable();
            $table->string('store_number', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('location_payment_type', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_locations');
    }
};
