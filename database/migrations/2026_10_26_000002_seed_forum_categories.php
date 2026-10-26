<?php

use App\Models\ForumCategory;
use Illuminate\Database\Migrations\Migration;

/**
 * Assuntos iniciais do forum.
 *
 * Sem nenhum assunto, o associado abre a tela de "novo topico" e encontra um
 * seletor vazio: o forum nasce quebrado e a diretoria precisa descobrir
 * sozinha que faltava um passo. Estes quatro sao um ponto de partida — a
 * diretoria renomeia, reordena ou desativa pela tela de Assuntos.
 *
 * So insere quando a tabela esta vazia, entao rodar de novo nao duplica nem
 * sobrescreve o que a diretoria tiver criado.
 */
return new class extends Migration
{
    private const INICIAIS = [
        ['Avisos da diretoria', 'Comunicados oficiais da associação.', 0],
        ['Dúvidas e ajuda', 'Pergunte para quem já passou por isso.', 1],
        ['Indicações e parcerias', 'Quem indica quem, e oportunidades entre associados.', 2],
        ['Eventos e encontros', 'O que está acontecendo no ecossistema.', 3],
    ];

    public function up(): void
    {
        if (ForumCategory::exists()) {
            return;
        }

        foreach (self::INICIAIS as [$nome, $descricao, $posicao]) {
            ForumCategory::create([
                'name' => $nome,
                'slug' => ForumCategory::uniqueSlug($nome),
                'description' => $descricao,
                'position' => $posicao,
                'is_active' => true,
            ]);
        }
    }

    public function down(): void
    {
        // Nao apaga nada: a essa altura pode haver topico escrito nesses
        // assuntos, e a conversa dos associados nao se desfaz numa migracao.
    }
};
