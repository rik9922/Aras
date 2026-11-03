<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('em_ba_first_shift_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ambassador_id')->unique();
            $table->unsignedBigInteger('first_shift_event_id');
            $table->dateTime('first_shift_at');
            $table->unsignedBigInteger('market_manager_id')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->string('suppressed_reason', 64)->nullable();
            $table->timestamp('suppressed_at')->nullable();
            $table->timestamps();

            $table->index('first_shift_event_id');
            $table->index('market_manager_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('em_ba_first_shift_alerts');
    }
};
