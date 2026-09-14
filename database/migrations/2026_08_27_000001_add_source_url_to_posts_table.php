<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link de origem da noticia.
 *
 * Guarda de onde o conteudo veio quando a noticia nasce de uma materia de
 * terceiro. Serve para dar credito e mandar o leitor para a fonte — e e a
 * partir dele que o painel busca titulo, resumo e capa.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'source_url')) {
                $table->string('source_url')->nullable()->after('category');
            }

            if (! Schema::hasColumn('posts', 'source_name')) {
                $table->string('source_name')->nullable()->after('source_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['source_url', 'source_name']);
        });
    }
};
