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
        Schema::create('recaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('recap_type', 32)->nullable();

            $table->string('ambassador_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('location_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('close_time')->nullable();
            $table->string('check_in_with_person')->nullable();

            $table->string('product_attachments1')->nullable();
            $table->boolean('promotion_product_status')->default(0);
            $table->string('tip', 100)->nullable();
            $table->string('product_attachments2')->nullable();

            $table->string('brand_dmeo_name')->nullable();
            $table->string('brand_photo')->nullable();

            $table->string('interaction_gender', 20)->nullable();
            $table->string('interaction_age', 100)->nullable();
            $table->string('total_interaction', 100)->nullable();

            $table->string('samples', 10)->nullable();
            $table->text('consumer_photo')->nullable();

            $table->string('brand_selling_id')->nullable();
            $table->string('sold_unit')->nullable();
            $table->decimal('buyback_amount', 10, 2)->nullable();

            $table->string('consumer_feedback')->nullable();
            $table->string('ambassador_feedback')->nullable();
            $table->unsignedInteger('consumer_interactions')->nullable();
            $table->unsignedInteger('consumer_sampled')->nullable();
            $table->text('event_summary')->nullable();
            $table->unsignedInteger('drinks_purchased_after_sampling')->nullable();
            $table->text('manager_comments')->nullable();
            $table->text('consumer_comments_suggestions')->nullable();

            $table->text('traffic_flow')->nullable();
            $table->tinyInteger('demographics_female')->unsigned()->nullable();
            $table->tinyInteger('demographics_male')->unsigned()->nullable();
            $table->string('demographics_age_range', 20)->nullable();
            $table->unsignedInteger('sampling_number')->nullable();
            $table->json('products_sold')->nullable();
            $table->decimal('receipt_total', 10, 2)->nullable();
            $table->decimal('op_parking_fee', 10, 2)->nullable();
            $table->boolean('store_aware_of_demo')->default(0);
            $table->boolean('store_prepared_for_demo')->default(0);
            $table->boolean('product_on_sale')->default(0);
            $table->boolean('coupons_rebates_available')->default(0);
            $table->boolean('enough_product_in_stock')->default(0);
            $table->boolean('case_display')->default(0);
            $table->string('case_display_count', 50)->nullable();
            $table->text('objections_to_sale')->nullable();
            $table->text('account_feedback')->nullable();
            $table->json('op_receipts')->nullable();
            $table->text('recap_attachments')->nullable();

            $table->string('state_id', 191)->nullable();
            $table->integer('brand_count')->nullable();
            $table->boolean('backup_brand_used')->default(false);
            $table->string('backup_brand_name', 255)->nullable();
            $table->unsignedInteger('giveaway_count')->nullable();
            $table->text('giveaway_comments')->nullable();

            $table->text('deny_reason')->nullable();
            $table->unsignedInteger('denied_by')->nullable();
            $table->timestamp('denied_at')->nullable();

            $table->string('receipt')->nullable();
            $table->string('traffic_location')->nullable();

            $table->longText('best_payload')->nullable();

            $table->boolean('is_approved')->default(0);
            $table->boolean('is_draft')->default(false);
            $table->boolean('is_expedited_requested')->default(false);
            $table->timestamp('expedited_requested_at')->nullable();
            $table->dateTime('approved_at')->nullable();

            $table->decimal('manager_override_amount', 10, 2)->nullable();
            $table->boolean('manager_no_show')->default(0);
            
            $table->string('status')->nullable();
            $table->dateTime('auto_deducted_at')->nullable();
            $table->string('auto_deduction_reason')->nullable();

            $table->longText('thc_products_sold')->nullable();
            $table->string('dosage')->nullable();

            $table->string('created_by', 191)->nullable();
            $table->string('updated_by', 191)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recaps');
    }
};
