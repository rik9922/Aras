<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('clients', 'parent_bill_to_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->unsignedBigInteger('parent_bill_to_id')->nullable()->after('user_id');
                $table->foreign('parent_bill_to_id', 'clients_parent_bill_to_fk')
                    ->references('id')->on('users')->nullOnDelete();
                $table->index('parent_bill_to_id', 'clients_parent_bill_to_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clients', 'parent_bill_to_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->dropForeign('clients_parent_bill_to_fk');
                $table->dropIndex('clients_parent_bill_to_idx');
                $table->dropColumn('parent_bill_to_id');
            });
        }
    }
};
