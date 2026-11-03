<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recap_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ambassador_id');
            $table->unsignedBigInteger('event_id');
            $table->string('recap_type', 50)->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->unique(['ambassador_id', 'event_id']);
            $table->index('event_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recap_drafts');
    }
};
