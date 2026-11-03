<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brand_bill_to_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['brand_id', 'client_id']);
            $table->index(['client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brand_bill_to_clients');
    }
};

