<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Destaque fixo da noticia.
 *
 * Antes o destaque era sempre a mais recente. Com esta coluna a associacao
 * escolhe qual materia fica em primeiro, independente da data — util quando a
 * noticia importante nao e a ultima publicada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'is_featured')) {
                $table->boolean('is_featured')->default(false)->after('is_published');

                // A listagem publica ordena sempre por estes dois juntos.
                $table->index(['is_featured', 'published_at']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['is_featured', 'published_at']);
            $table->dropColumn('is_featured');
        });
    }
};
