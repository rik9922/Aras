<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manager_market_user', function (Blueprint $table) {
            $table->decimal('extra_pay_budget', 12, 2)->nullable()->after('market_id');
        });
    }

    public function down(): void
    {
        Schema::table('manager_market_user', function (Blueprint $table) {
            $table->dropColumn('extra_pay_budget');
        });
    }
};

