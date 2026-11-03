<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ambassador_liquor_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('has_liquor_license')->default(false);
            $table->string('license_number', 100)->nullable();
            $table->string('issuing_state', 50)->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('document_on_file')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $now = now();
        $ambassadorIds = DB::table('users')->where('role_id', 5)->pluck('id');
        foreach ($ambassadorIds as $userId) {
            DB::table('ambassador_liquor_licenses')->insert([
                'user_id' => $userId,
                'has_liquor_license' => false,
                'license_number' => null,
                'issuing_state' => null,
                'issued_at' => null,
                'expires_at' => null,
                'document_on_file' => false,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ambassador_liquor_licenses');
    }
};
