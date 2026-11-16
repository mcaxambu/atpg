<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mensalidade dos associados.
 *
 * Uma linha por EMPRESA e por COMPETENCIA (o mes a que a cobranca se refere),
 * com unicidade garantida no banco: gerar o mes duas vezes — por clique
 * repetido, por cron que rodou duas vezes — nao pode cobrar duas vezes.
 *
 * Competencia e vencimento sao coisas diferentes e ficam separadas de
 * proposito: a mensalidade de novembro pode vencer em 10/12. Juntar os dois
 * num campo so faz o relatorio do ano mentir na virada.
 *
 * Os campos `cobranca_*` ficam vazios por enquanto: a cobranca automatica
 * (Asaas ou outro) ainda nao foi decidida, e o resto do controle — quem deve,
 * quem pagou, recibo — nao depende dela.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensalidades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Sempre o dia 1 do mes de referencia.
            $table->date('competencia');
            $table->date('vencimento');
            $table->decimal('valor', 10, 2);

            $table->string('status', 16)->default('pendente');
            $table->date('pago_em')->nullable();
            $table->string('forma_pagamento', 30)->nullable();
            $table->text('observacao')->nullable();

            // Para quando houver cobranca automatica.
            $table->string('cobranca_externa_id')->nullable();
            $table->text('cobranca_url')->nullable();

            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['company_id', 'competencia']);
            $table->index(['competencia', 'status']);
            $table->index(['status', 'vencimento']);
        });

        Schema::table('companies', function (Blueprint $table) {
            // Associada isenta nao entra na geracao do mes. Existe honorario,
            // fundador e caso combinado — e a alternativa seria gerar e
            // cancelar todo mes, o que polui o historico.
            $table->boolean('mensalidade_isenta')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('mensalidade_isenta');
        });

        Schema::dropIfExists('mensalidades');
    }
};
