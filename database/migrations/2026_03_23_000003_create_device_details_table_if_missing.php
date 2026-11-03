<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('device_details')) {
            return;
        }

        Schema::create('device_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('device_token', 255)->nullable();
            $table->string('platform', 25)->nullable();
            $table->string('app_version', 20)->nullable();
            $table->tinyInteger('is_user_loggedin')->nullable();
            $table->string('time_zone', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Only drop if the table exists.
        if (Schema::hasTable('device_details')) {
            Schema::dropIfExists('device_details');
        }
    }
};

