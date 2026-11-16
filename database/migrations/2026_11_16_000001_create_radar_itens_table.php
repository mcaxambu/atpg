<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Radar: o que a varredura encontrou, esperando decisao da diretoria.
 *
 * Fila de SUGESTAO, nao de publicacao. Nada daqui vai ao ar sozinho — a data
 * e o campo que a leitura automatica erra com mais facilidade, e data errada
 * na agenda da associacao e pior do que agenda vazia.
 *
 * Guardamos o texto da fonte so para a triagem na tela; o que vai ao portal e
 * o resumo escrito do zero, com o link da fonte junto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('radar_itens', function (Blueprint $table) {
            $table->id();

            // ----- De onde veio -----
            $table->string('fonte', 40);
            $table->text('url');
            // A URL e longa demais para indice unico em MySQL; o hash resolve
            // e e o que impede o mesmo link de voltar toda madrugada.
            $table->char('url_hash', 64)->unique();
            $table->string('titulo_original')->nullable();
            $table->text('resumo_original')->nullable();
            $table->dateTime('publicado_em')->nullable();

            // ----- O que a leitura automatica extraiu -----
            $table->string('tipo', 16)->nullable();
            $table->string('titulo')->nullable();
            $table->text('resumo')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->string('hora_inicio', 5)->nullable();
            $table->string('local')->nullable();
            $table->string('organizador')->nullable();
            $table->text('inscricao_url')->nullable();
            $table->unsignedTinyInteger('confianca')->nullable();
            $table->text('motivo')->nullable();

            // ----- A decisao -----
            $table->string('status', 16)->default('novo');
            $table->dateTime('decidido_em')->nullable();
            $table->foreignId('decidido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('publicado');
            $table->text('erro')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('data_inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('radar_itens');
    }
};
