<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambassador_market', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambassador_id')->constrained('ambassadors')->cascadeOnDelete();
            $table->foreignId('market_id')->constrained('markets')->cascadeOnDelete();
            $table->unique(['ambassador_id', 'market_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambassador_market');
    }
};
