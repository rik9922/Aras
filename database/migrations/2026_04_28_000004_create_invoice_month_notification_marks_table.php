<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoice_month_notification_marks')) {
            return;
        }

        Schema::create('invoice_month_notification_marks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->string('month', 7); // YYYY-MM
            $table->string('type', 64);
            $table->timestamp('triggered_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['client_id', 'month', 'type'], 'imm_client_month_type_unique');
            $table->index(['month', 'type'], 'imm_month_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_month_notification_marks');
    }
};

