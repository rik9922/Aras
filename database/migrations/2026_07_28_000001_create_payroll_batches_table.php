<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_batches', function (Blueprint $table) {
            $table->id();

            $table->string('batch_number')->unique();

            $table->date('period_start');
            $table->date('period_end');

            $table->enum('status', [
                'created',
                'processing',
                'completed',
                'failed',
            ])->default('created');

            $table->unsignedInteger('total_records')->default(0);
            $table->decimal('total_amount', 12, 2)->default(0.00);

            $table->foreignId('generated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('processed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('generated_at')->nullable();
            $table->dateTime('processed_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_batches');
    }
};
