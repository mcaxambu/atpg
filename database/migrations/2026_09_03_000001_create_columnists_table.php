<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Colunistas do portal.
 *
 * O colunista NAO e um usuario novo: e um membro ou uma empresa que ja existe,
 * marcado como colunista no proprio cadastro. Esta tabela e o perfil publico
 * dessa marcacao.
 *
 * Existe como tabela propria, e nao como duas colunas em members/companies,
 * por tres motivos concretos:
 *
 *   - O endereco /colunas/{slug} precisa ser unico. Slug de membro e slug de
 *     empresa vivem em tabelas diferentes e podem colidir; aqui a unicidade e
 *     garantida pelo banco.
 *   - A noticia guarda UMA referencia (columnist_id), em vez de dois campos
 *     onde um estaria sempre vazio.
 *   - Nome de assinatura e apresentacao sao dados da coluna, nao do cadastro
 *     de membro nem do de empresa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('columnists', function (Blueprint $table) {
            $table->id();

            // Exatamente um dos dois. A regra e garantida no model, e os
            // indices unicos impedem o mesmo membro/empresa duas vezes.
            $table->foreignId('member_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->unique()->constrained()->cascadeOnDelete();

            // Assinatura. Vazio significa "usa o nome do cadastro" — evita que
            // os dois nomes divirjam quando alguem corrige so um deles.
            $table->string('name')->nullable();
            $table->string('slug')->unique();

            // Apresentacao curta na pagina do colunista. Vazio cai para o
            // resumo do membro ou a descricao da empresa.
            $table->string('headline')->nullable();
            $table->text('bio')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('columnists');
    }
};
