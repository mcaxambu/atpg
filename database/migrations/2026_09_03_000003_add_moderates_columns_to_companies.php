<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Empresa delegada para moderar as colunas do portal.
 *
 * ATENCAO ao alcance disto: o painel da empresa foi desenhado para ela ver
 * apenas os PROPRIOS dados — vagas dela, colaboradores dela. Esta marcacao
 * abre uma excecao deliberada nesse limite: a empresa marcada passa a decidir
 * o que o portal inteiro publica na secao de colunas.
 *
 * Por isso e uma coluna so, explicita, ligada no cadastro pela diretoria — e
 * nao um efeito colateral de outra configuracao.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (! Schema::hasColumn('companies', 'moderates_columns')) {
                $table->boolean('moderates_columns')->default(false)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('moderates_columns');
        });
    }
};
