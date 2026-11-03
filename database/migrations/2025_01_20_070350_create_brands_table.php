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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete(); //CLEANUP

            $table->foreignId('parent_brand_id')
                ->nullable()
                ->constrained('brands')
                ->nullOnDelete();

            $table->string('brand_name')->nullable();
            $table->string('notes')->nullable();
            $table->string('budget_alert_email')->nullable();

            $table->foreignId('brand_category_id')
                ->nullable()
                ->constrained('brand_categories')
                ->cascadeOnDelete();

            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
