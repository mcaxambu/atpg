<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('description');
            $table->string('email')->nullable()->after('contact_name');
            $table->string('whatsapp')->nullable()->after('email');
            $table->string('site_url')->nullable()->after('whatsapp');
            $table->boolean('is_active')->default(true)->after('site_url');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['contact_name', 'email', 'whatsapp', 'site_url', 'is_active']);
        });
    }
};
