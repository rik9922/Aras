<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'favorite_coffee_shop')) {
                $table->string('favorite_coffee_shop')->nullable()->after('mailing_zip_code');
            }
            if (! Schema::hasColumn('users', 'favorite_restaurant')) {
                $table->string('favorite_restaurant')->nullable()->after('favorite_coffee_shop');
            }
            if (! Schema::hasColumn('users', 'favorite_place_to_shop')) {
                $table->string('favorite_place_to_shop')->nullable()->after('favorite_restaurant');
            }
            if (! Schema::hasColumn('users', 'favorite_charity')) {
                $table->string('favorite_charity')->nullable()->after('favorite_place_to_shop');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = [
                'favorite_coffee_shop',
                'favorite_restaurant',
                'favorite_place_to_shop',
                'favorite_charity',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
