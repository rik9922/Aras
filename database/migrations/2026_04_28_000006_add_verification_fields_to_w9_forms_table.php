<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('w9_forms', function (Blueprint $table) {
            if (! Schema::hasColumn('w9_forms', 'verification_status')) {
                $table->string('verification_status', 32)->nullable()->after('quickbooks_vendor_id');
            }
            if (! Schema::hasColumn('w9_forms', 'verification_provider')) {
                $table->string('verification_provider', 64)->nullable()->after('verification_status');
            }
            if (! Schema::hasColumn('w9_forms', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verification_provider');
            }
            if (! Schema::hasColumn('w9_forms', 'verification_error')) {
                $table->text('verification_error')->nullable()->after('verified_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('w9_forms', function (Blueprint $table) {
            $cols = [];
            foreach (['verification_status', 'verification_provider', 'verified_at', 'verification_error'] as $c) {
                if (Schema::hasColumn('w9_forms', $c)) {
                    $cols[] = $c;
                }
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

