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
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_location_id')->constrained('event_locations');

            $table->string('city')->nullable();
            $table->string('event_address')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();

            $table->foreignId('department_id')
                ->constrained('event_departments')
                ->cascadeOnDelete();

            $table->foreignId('event_type_id')->constrained('event_types');

            $table->unsignedInteger('position');

            $table->boolean('age_restricted')->default(0);

            $table->boolean('apply_event')->default(0);

            $table->boolean('is_thc_event')->default(0);

            $table->foreignId('distributor_id')
                ->constrained('event_distributors')
                ->cascadeOnDelete();

            $table->foreignId('brand_id')->constrained('brands');

            $table->foreignId('market_id')->constrained('markets');

            $table->foreignId('manager_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->dateTime('start_time');
            $table->dateTime('end_time');

            $table->text('varietals')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();

            $table->boolean('payment_status')->default(0);
            $table->string('payment_type')->nullable();

            $table->string('expedite_fee')->nullable();
            $table->string('shipping_fee')->nullable();
            $table->string('parking_fee')->nullable();

            $table->time('drive_time')->nullable();

            $table->boolean('receipt')->default(0);
            $table->boolean('kit')->default(0);

            $table->text('kit_description')->nullable();
            $table->boolean('kit_delivered')->default(0);

            $table->text('uniform')->nullable();

            $table->text('attachments')->nullable();
            $table->dateTime('booked_date');
            $table->string('booked_by');

            $table->boolean('integrate_with_best')->default(0);
            $table->dateTime('best_synced_at')->nullable();
            $table->enum('best_status', ['pending', 'processing', 'success', 'failed'])->nullable()->default('pending');
            $table->json('best_response')->nullable();
            $table->json('best_payload')->nullable();
            $table->json('best_event_data')->nullable();

            $table->tinyInteger('event_status')
                ->default(0)
                ->comment('0=Pending,1=Completed,2=Canceled');

            $table->boolean('admin_status')->default(0);

            $table->foreignId('admin_status_set_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('admin_status_set_at')->nullable();

            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();

            $table->string('cancellation_reason')->nullable();

            $table->string('qb_publish_status')->nullable();
            $table->string('qb_publish_error')->nullable();
            $table->dateTime('qb_published_at')->nullable();
            $table->string('ambassador_id');

            $table->boolean('pinata_pay')->default(0)->comment('Adds $5/hour to all ambassadors for this event if enabled');
            $table->string('event_name');
            $table->foreignId('client_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('split_cups')->default(0);

            $table->decimal('labor_amount', 8, 2)->default(0);
            $table->decimal('buyback_amount', 8, 2)->default(0);
            $table->decimal('cups_amount', 8, 2)->default(0);

            $table->string('timezone')->nullable();
            $table->string('timezone_label')->nullable();
            $table->boolean('recap_needed')->default(0);
            $table->string('recap_status')->nullable();
            $table->decimal('recap_payout_amount', 10, 2)->default(0);
            $table->string('region')->default(0);
            $table->string('budget_approval_status', 32)->nullable();
            $table->dateTime('budget_approval_requested_at')->nullable();
            $table->foreignId('budget_approval_resolved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('budget_approval_resolved_at')->nullable();
            $table->string('budget_approval_note')->nullable();

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
