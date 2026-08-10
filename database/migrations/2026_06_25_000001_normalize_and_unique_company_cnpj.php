<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('companies')->whereNotNull('cnpj')->orderBy('id')->each(function ($company) {
            $digits = preg_replace('/\D/', '', (string) $company->cnpj);

            // Evita falha na criação do índice único se já existir CNPJ duplicado/mascarado.
            $exists = DB::table('companies')
                ->where('id', '!=', $company->id)
                ->where('cnpj', $digits)
                ->exists();

            DB::table('companies')->where('id', $company->id)->update([
                'cnpj' => $exists ? null : $digits,
            ]);
        });

        Schema::table('companies', function ($table) {
            $table->unique('cnpj');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function ($table) {
            $table->dropUnique(['cnpj']);
        });
    }
};
