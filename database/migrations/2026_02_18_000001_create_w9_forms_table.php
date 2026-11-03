<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('w9_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('business_name')->nullable();
            $table->enum('tax_classification', ['individual', 'c_corp', 's_corp', 'partnership', 'trust', 'llc', 'other']);
            $table->string('llc_tax_classification')->nullable();
            $table->string('other_tax_classification')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('zip_code');
            $table->string('ssn_ein');
            $table->boolean('is_ein')->default(false);
            $table->string('signature');
            $table->date('signature_date');
            $table->string('quickbooks_vendor_id')->nullable();
            $table->boolean('is_1099_vendor')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('w9_submitted')->default(false)->after('quickbooks_vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('w9_forms');
    }
};
