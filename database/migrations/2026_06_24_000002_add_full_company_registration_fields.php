<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('legal_name')->nullable()->after('name');
            $table->string('cnpj', 32)->nullable()->after('legal_name');
            $table->string('state', 2)->nullable()->after('city');
            $table->string('zip_code', 16)->nullable()->after('state');
            $table->string('address')->nullable()->after('zip_code');
            $table->string('address_number', 32)->nullable()->after('address');
            $table->string('neighborhood')->nullable()->after('address_number');
            $table->string('contact_role')->nullable()->after('contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'legal_name',
                'cnpj',
                'state',
                'zip_code',
                'address',
                'address_number',
                'neighborhood',
                'contact_role',
            ]);
        });
    }
};
