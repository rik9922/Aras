<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_distributor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('distributor_id')->constrained('event_distributors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['client_id', 'distributor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_distributor');
    }
};
