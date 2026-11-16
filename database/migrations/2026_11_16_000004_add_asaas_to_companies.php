<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * O id do cliente no Asaas fica na empresa.
 *
 * Guardar evita procurar a cada cobranca e, principalmente, evita criar o
 * mesmo cliente duas vezes quando a conexao cai depois de o Asaas gravar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('asaas_customer_id', 40)->nullable()->after('mensalidade_isenta');
        });

        Schema::table('mensalidades', function (Blueprint $table) {
            $table->dateTime('cobranca_enviada_em')->nullable()->after('cobranca_url');
        });
    }

    public function down(): void
    {
        Schema::table('mensalidades', function (Blueprint $table) {
            $table->dropColumn('cobranca_enviada_em');
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('asaas_customer_id');
        });
    }
};
