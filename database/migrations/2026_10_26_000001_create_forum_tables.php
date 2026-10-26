<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Forum da associacao: categorias, topicos e respostas.
 *
 * Fechado aos associados logados por decisao do usuario — nada aqui aparece no
 * site publico. Quem escreve e um usuario do painel (empresa ou membro), e a
 * assinatura sai do cadastro dele: e justamente a identidade real que separa
 * este forum de um grupo de mensagens qualquer.
 *
 * `last_reply_at` e `replies_count` ficam gravados no topico de proposito. A
 * lista do forum ordena por atividade e mostra a contagem em toda visita;
 * calcular isso por subconsulta a cada carregamento e trabalho repetido para
 * sempre, por uma informacao que muda poucas vezes ao dia.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->string('icon', 40)->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('forum_topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('body');

            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_closed')->default(false);
            // Oculto e diferente de apagado: a diretoria tira da vista sem
            // destruir o que foi escrito, e pode voltar atras.
            $table->boolean('is_hidden')->default(false);

            $table->unsignedInteger('replies_count')->default(0);
            $table->timestamp('last_reply_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['forum_category_id', 'is_pinned', 'last_reply_at']);
        });

        Schema::create('forum_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('forum_topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('body');
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['forum_topic_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_replies');
        Schema::dropIfExists('forum_topics');
        Schema::dropIfExists('forum_categories');
    }
};
