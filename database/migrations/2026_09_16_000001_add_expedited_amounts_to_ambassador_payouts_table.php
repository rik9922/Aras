<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ambassador_payouts', function (Blueprint $table) {
            $table->decimal('original_amount', 10, 2)->nullable()->after('amount');
            $table->decimal('expedited_fee', 10, 2)->default(0)->after('original_amount');
        });
    }

    public function down(): void
    {
        Schema::table('ambassador_payouts', function (Blueprint $table) {
            $table->dropColumn(['original_amount', 'expedited_fee']);
        });
    }
};
