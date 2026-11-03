<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->foreignId('dwolla_funding_source_id')
                ->nullable()
                ->after('dwolla_transfer_id')
                ->constrained('dwolla_funding_sources')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dwolla_funding_source_id');
        });
    }
};
