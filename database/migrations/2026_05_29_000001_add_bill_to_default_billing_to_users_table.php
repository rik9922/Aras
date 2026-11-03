<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'default_labor_amount')) {
                $table->decimal('default_labor_amount', 10, 2)->nullable()->after('address');
            }
            if (! Schema::hasColumn('users', 'default_buyback_amount')) {
                $table->decimal('default_buyback_amount', 10, 2)->nullable()->after('default_labor_amount');
            }
            if (! Schema::hasColumn('users', 'default_cups_amount')) {
                $table->decimal('default_cups_amount', 10, 2)->nullable()->after('default_buyback_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['default_labor_amount', 'default_buyback_amount', 'default_cups_amount'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
