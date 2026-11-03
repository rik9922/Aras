<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (! Schema::hasColumn('brands', 'parent_brand_id')) {
                $table->foreignId('parent_brand_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('brands')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            if (Schema::hasColumn('brands', 'parent_brand_id')) {
                $table->dropConstrainedForeignId('parent_brand_id');
            }
        });
    }
};

