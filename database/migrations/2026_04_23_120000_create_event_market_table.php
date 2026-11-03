<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('event_market')) {
            Schema::create('event_market', function (Blueprint $table) {
                $table->id();
                $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
                $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['event_id', 'market_id']);
                $table->index(['market_id', 'event_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_market');
    }
};
