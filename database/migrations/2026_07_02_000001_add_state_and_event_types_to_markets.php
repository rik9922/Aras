<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('markets', 'state')) {
            Schema::table('markets', function (Blueprint $table) {
                $table->string('state')->nullable()->after('location');
            });
        }

        if (!Schema::hasTable('market_event_type')) {
            Schema::create('market_event_type', function (Blueprint $table) {
                $table->unsignedBigInteger('market_id');
                $table->unsignedBigInteger('event_type_id');
                $table->primary(['market_id', 'event_type_id']);
                $table->index('market_id');
                $table->index('event_type_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('market_event_type');
        Schema::table('markets', function (Blueprint $table) {
            $table->dropColumn('state');
        });
    }
};
