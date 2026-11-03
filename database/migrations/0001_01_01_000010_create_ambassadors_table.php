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
        Schema::create('ambassadors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            // Ambassador preferences / profile
            $table->string('nickname')->nullable();
            $table->date('dob');
            $table->string('venmo')->nullable();
            $table->string('region', 191)->nullable();
            $table->string('timezone', 191)->nullable();

            // certificates and preferences
            $table->boolean('titos_trained')->default(0);
            $table->boolean('thc_quiz_passed')->default(0);
            $table->boolean('male_ba_lgbtq_ok')->default(0);
            $table->boolean('attended_trainings')->default(0);
            $table->boolean('tips_certified')->default(0);
            $table->boolean('has_permit')->default(0);

            // legal address
            $table->string('legal_address')->nullable();
            $table->string('legal_city')->nullable();
            $table->char('legal_state', 3)->nullable();
            $table->integer('legal_zip_code')->nullable();

            $table->boolean('same_as_legal')->default(0);

            // mailing address
            $table->string('mailing_address')->nullable();
            $table->string('mailing_city', 100)->nullable();
            $table->char('mailing_state', 3)->nullable();
            $table->integer('mailing_zip_code')->nullable();

            // lat/long
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();

            // Ambassador personal favourites 
            $table->string('favorite_coffee_shop')->nullable();
            $table->string('favorite_restaurant')->nullable();
            $table->string('favorite_place_to_shop')->nullable();
            $table->string('favorite_charity')->nullable();
            
            $table->date('hired')->nullable();
            $table->decimal('wage', 8,2)->default(0);
            $table->string('shirt_size', 10)->nullable();
            $table->string('dwolla_customer_id')->nullable();
            $table->string('verification_status', 20)->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambassadors');
    }
};
