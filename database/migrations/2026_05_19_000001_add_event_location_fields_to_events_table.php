<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (! Schema::hasColumn('events', 'event_address')) {
                $table->string('event_address')->nullable()->after('city');
            }
            if (! Schema::hasColumn('events', 'latitude')) {
                $table->decimal('latitude', 11, 8)->nullable()->after('event_address');
            }
            if (! Schema::hasColumn('events', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $columns = ['event_address', 'latitude', 'longitude'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('events', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
