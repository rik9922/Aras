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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('venue_name')->nullable();
            $table->foreignId('venue_type_id')->nullable()->constrained('venues')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('event_departments')->onDelete('set null');
            $table->integer('position')->nullable();
            $table->tinyInteger('age_restricted')->default(0);
            $table->tinyInteger('apply_event')->default(0);
            $table->foreignId('distributor_id')->nullable()->constrained('event_distributors')->onDelete('set null');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            $table->foreignId('market_id')->nullable()->constrained('markets')->onDelete('set null');
            $table->foreignId('manager_id')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->text('varietals')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->tinyInteger('payment_status')->default(0);
            $table->decimal('expedite_fee', 10,2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->decimal('parking_fee', 10, 2)->default(0);
            $table->decimal('drive_time', 10, 2)->default(0);
            $table->string('payment_type')->nullable();
            $table->tinyInteger('receipt')->default(0);
            $table->tinyInteger('kit')->default(0);
            $table->text('kit_description')->nullable();
            $table->tinyInteger('kit_delivered')->default(0);
            $table->string('uniform')->nullable();
            $table->string('attachments')->nullable();
            $table->date('booked_date')->nullable();
            $table->string('booked_by')->nullable();
            $table->tinyInteger('integrate_with_best')->default(0);
            $table->enum('event_status', ['0', '1', '2'])->default('0');
            $table->string('ambassador_id')->nullable();                    // "1,2,3,4" etc.
            $table->tinyInteger('pinata_pay')->default(0);
            $table->string('event_name')->nullable();
            $table->foreignId('event_type_id')->nullable()->constrained('event_types')->onDelete('set null');
            $table->json('client_id')->nullable();                         // ← belongs to user (array of user IDs)
            $table->string('timezone')->default('UTC');
            $table->tinyInteger('recap_needed')->default(0);
            $table->string('region', 191)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
