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
        Schema::create('assign_ambassadors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('ambassador_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['0', '1'])->default('0');
            $table->decimal('override_pay', 8, 2)->default(0.00);
            $table->string('additional_pay_reason', 50)->nullable();
            $table->text('additional_pay_note')->nullable();

            $table->unsignedTinyInteger('pinata_amount')->default(0);
            $table->boolean('is_no_show')->default(false);

            $table->decimal('ambassador_price', 8, 2)->default(0.00);
            $table->decimal('latitude', 11, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->timestamp('event_date_time')->nullable();

            $table->boolean('is_at_event_location')->default(false);
            $table->decimal('check_in_event_latitude', 11, 8)->nullable();
            $table->decimal('check_in_event_longitude', 11, 8)->nullable();
            $table->unsignedInteger('distance_from_event_meters')->nullable();
            $table->boolean('forced_check_in')->default(false);
            $table->boolean('late_checkin_notification_sent')->default(false);
            $table->timestamp('late_checkin_notification_sent_at')->nullable();
            $table->boolean('check_in_ready_notification_sent')->default(false);
            $table->timestamp('check_in_ready_notification_sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assign_ambassadors');
    }
};
