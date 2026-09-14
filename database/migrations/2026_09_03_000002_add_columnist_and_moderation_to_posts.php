<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Coluna e uma noticia com autor.
 *
 * Nao ha tabela separada de proposito: assim a coluna herda editor visual,
 * capa, destaque, SEO e sitemap que a noticia ja tem. O que a distingue e ter
 * `columnist_id` preenchido.
 *
 * A moderacao entra junto porque agora existe texto escrito por gente de fora
 * da diretoria: o colunista escreve no painel dele e a associacao aprova, o
 * mesmo fluxo das vagas. As noticias que ja existem sao da propria diretoria,
 * entao nascem aprovadas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (! Schema::hasColumn('posts', 'columnist_id')) {
                $table->foreignId('columnist_id')->nullable()->after('category')
                    ->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('posts', 'status')) {
                $table->string('status', 16)->default('approved')->after('is_featured');
                $table->text('rejection_reason')->nullable()->after('status');
                $table->timestamp('reviewed_at')->nullable()->after('rejection_reason');
                $table->foreignId('reviewed_by')->nullable()->after('reviewed_at')
                    ->constrained('users')->nullOnDelete();
            }
        });

        // Tudo que ja existia foi publicado pela diretoria: fica aprovado.
        DB::table('posts')->whereNull('status')->orWhere('status', '')->update(['status' => 'approved']);

        Schema::table('posts', function (Blueprint $table) {
            $table->index(['status', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_published']);
            $table->dropConstrainedForeignId('columnist_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['status', 'rejection_reason', 'reviewed_at']);
        });
    }
};
