<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quickbooks_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('realm_id')->nullable()->index();
            $table->string('entity_type')->index();
            $table->string('entity_id')->index();
            $table->string('operation', 32)->index();
            $table->timestamp('quickbooks_updated_at')->nullable();
            $table->string('event_hash')->unique();
            $table->json('payload');
            $table->json('headers')->nullable();
            $table->string('status', 32)->default('received')->index();
            $table->text('failure_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'operation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quickbooks_webhook_events');
    }
};
