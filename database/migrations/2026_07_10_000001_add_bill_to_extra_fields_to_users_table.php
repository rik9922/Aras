<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable()->after('address');
            }
            if (! Schema::hasColumn('users', 'primary_billing_contact')) {
                $table->string('primary_billing_contact')->nullable()->after('company_name');
            }
            if (! Schema::hasColumn('users', 'country')) {
                $table->string('country', 100)->nullable()->default('USA')->after('primary_billing_contact');
            }
            if (! Schema::hasColumn('users', 'notes')) {
                $table->text('notes')->nullable()->after('country');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['company_name', 'primary_billing_contact', 'country', 'notes'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
