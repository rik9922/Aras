<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('recap_daily_summary_marks')) {
            return;
        }

        Schema::create('recap_daily_summary_marks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recap_id');
            $table->timestamp('last_notified_updated_at');
            $table->date('summary_date')->nullable();
            $table->timestamps();

            $table->unique('recap_id', 'rdsm_recap_id_unique');
            $table->index('summary_date', 'rdsm_summary_date_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recap_daily_summary_marks');
    }
};
