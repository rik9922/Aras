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
        Schema::create('dwolla_webhook_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('dwolla_subscription_id')->unique();
            $table->string('url');
            $table->boolean('paused')->default(false);
            $table->timestamp('dwolla_created_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dwolla_webhook_subscriptions');
    }
};
