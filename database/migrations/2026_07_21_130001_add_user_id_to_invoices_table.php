<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('event_id')
                    ->constrained('users')
                    ->nullOnDelete()
                    ->comment('User who created or last saved the invoice');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'user_id')) {
                $table->dropConstrainedForeignId('user_id');
            }
        });
    }
};
