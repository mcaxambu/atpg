<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'registration_source')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->string('registration_source', 32)->default('admin')->after('is_active');
            });
        }

        DB::table('companies')
            ->whereNull('registration_source')
            ->orWhere('registration_source', '')
            ->update(['registration_source' => 'admin']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('companies', 'registration_source')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropColumn('registration_source');
            });
        }
    }
};
