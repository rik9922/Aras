<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aras_documents', function (Blueprint $table) {
            $table->unsignedBigInteger('file_size')->nullable()->after('url'); // bytes
        });
    }

    public function down(): void
    {
        Schema::table('aras_documents', function (Blueprint $table) {
            $table->dropColumn('file_size');
        });
    }
};
