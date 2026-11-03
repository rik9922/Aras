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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();

            $table->integer('zip_code')->nullable();
            $table->string('profile_image')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('remember_token', 100)->nullable();
            $table->unsignedInteger('event_location_id')->nullable();
            
            $table->date('dob')->nullable();
            $table->string('brand_id')->nullable();
            $table->integer('contract_recieved')->default(1);
            $table->unsignedInteger('distributor_id')->nullable();

            $table->string('phone', 50)->nullable();
            $table->enum('status', ['0', '1', '2'])->default('1');
            $table->string('city')->nullable();
            $table->integer('state_id')->nullable();
            $table->string('address')->nullable();
            $table->string('state', 20)->nullable();
            $table->string('legal_address')->nullable();
            $table->string('legal_city')->nullable();
            $table->bigInteger('legal_state_id')->nullable();
            $table->string('legal_zip_code', 20)->nullable();
            $table->tinyInteger('same_as_legal')->default(0);
            $table->string('mailing_address')->nullable();
            $table->string('mailing_city', 100)->nullable();
            $table->bigInteger('mailing_state_id')->nullable();
            $table->string('mailing_zip_code', 20)->nullable();
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->tinyInteger('titos_trained')->default(0);
            $table->tinyInteger('thc_quiz_passed')->default(0);
            $table->tinyInteger('male_ba_lgbtq_ok')->default(0);
            $table->tinyInteger('attended_trainings')->default(0);
            $table->tinyInteger('tips_certified')->default(0);
            $table->tinyInteger('has_permit')->default(0);
            $table->string('venmo')->nullable();
            $table->string('nickname')->nullable();
            $table->string('instagram')->nullable();
            $table->string('market_id')->nullable();
            $table->string('venue_name')->nullable();
            $table->bigInteger('venue_type')->nullable();
            $table->string('hired')->nullable();
            $table->string('wage')->nullable();
            $table->string('shirt_size', 10)->nullable();
            $table->string('dwolla_customer_id')->nullable();
            $table->string('verification_status', 20)->nullable();
            $table->string('region', 191)->nullable();
            $table->string('timezone', 191)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
