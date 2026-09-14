<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Destaque fixo da noticia e link externo abrindo em outra aba.
 */
class PostFeaturedAndLinksTest extends TestCase
{
    use RefreshDatabase;

    private function noticia(array $overrides = []): Post
    {
        static $n = 0;
        $n++;

        return Post::create(array_merge([
            'title' => "Notícia {$n}",
            'slug' => "noticia-{$n}",
            'excerpt' => 'Resumo.',
            'body' => 'Texto da notícia com tamanho suficiente para validar.',
            'category' => 'Notícias',
            'is_published' => true,
            'published_at' => now()->subDays($n),
        ], $overrides));
    }

    // ----- Destaque -----

    #[Test]
    public function a_marcada_como_destaque_vem_antes_da_mais_recente(): void
    {
        $antiga = $this->noticia(['title' => 'Antiga em destaque', 'published_at' => now()->subMonth(), 'is_featured' => true]);
        $recente = $this->noticia(['title' => 'Recente sem destaque', 'published_at' => now()->subHour()]);

        $ordem = Post::published()->inFeedOrder()->pluck('title')->all();

        $this->assertSame([$antiga->title, $recente->title], $ordem);
    }

    #[Test]
    public function sem_nenhuma_marcada_a_mais_recente_continua_em_primeiro(): void
    {
        $this->noticia(['title' => 'Mais velha', 'published_at' => now()->subMonth()]);
        $recente = $this->noticia(['title' => 'Mais nova', 'published_at' => now()->subHour()]);

        $this->assertSame($recente->title, Post::published()->inFeedOrder()->first()->title);
    }

    #[Test]
    public function o_cartao_de_destaque_da_listagem_e_a_marcada(): void
    {
        $this->noticia(['title' => 'Recente qualquer', 'published_at' => now()->subHour()]);
        $destaque = $this->noticia(['title' => 'Escolhida pela diretoria', 'published_at' => now()->subMonth(), 'is_featured' => true]);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertViewHas('featured', fn ($featured) => $featured->is($destaque));
    }

    #[Test]
    public function o_destaque_tambem_vale_na_home(): void
    {
        $this->noticia(['title' => 'Recente qualquer', 'published_at' => now()->subHour()]);
        $destaque = $this->noticia(['title' => 'Escolhida pela diretoria', 'published_at' => now()->subMonth(), 'is_featured' => true]);

        $this->get(route('home'))
            ->assertOk()
            ->assertViewHas('latestPosts', fn ($posts) => $posts->first()->is($destaque));
    }

    #[Test]
    public function o_painel_grava_e_desmarca_o_destaque(): void
    {
        $admin = User::factory()->create();
        $post = $this->noticia();

        $base = [
            'title' => $post->title,
            'body' => $post->body,
            'category' => 'Notícias',
            'is_published' => '1',
        ];

        $this->actingAs($admin)
            ->put(route('admin.cms.posts.update', $post), $base + ['is_featured' => '1'])
            ->assertRedirect();

        $this->assertTrue($post->fresh()->is_featured);

        // Caixa desmarcada nao envia nada: o campo precisa voltar a false.
        $this->actingAs($admin)
            ->put(route('admin.cms.posts.update', $post), $base)
            ->assertRedirect();

        $this->assertFalse($post->fresh()->is_featured);
    }

    #[Test]
    public function destaque_despublicado_nao_aparece_no_portal(): void
    {
        $this->noticia(['title' => 'Destaque oculto', 'is_featured' => true, 'is_published' => false, 'published_at' => null]);
        $publicada = $this->noticia(['title' => 'Publicada normal']);

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertDontSee('Destaque oculto')
            ->assertSee($publicada->title);
    }

    // ----- Link externo -----

    #[Test]
    public function link_externo_abre_em_outra_aba(): void
    {
        $post = $this->noticia([
            'body' => 'Leia mais em [matéria completa](https://exemplo.com.br/materia).',
        ]);

        $html = $post->rendered_body;

        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener nofollow"', $html);
    }

    #[Test]
    public function link_do_proprio_portal_continua_na_mesma_aba(): void
    {
        $interno = rtrim(config('app.url'), '/').'/vagas';

        $post = $this->noticia([
            'body' => "Veja as [vagas abertas]({$interno}).",
        ]);

        $html = $post->rendered_body;

        $this->assertStringContainsString($interno, $html);
        $this->assertStringNotContainsString('target="_blank"', $html);
    }

    #[Test]
    public function o_tratamento_vale_para_os_outros_conteudos(): void
    {
        // O corpo da vaga e da pagina passam pelo mesmo renderizador.
        $vaga = \App\Models\JobOpening::factory()->create([
            'description' => 'Saiba mais em [nosso site](https://exemplo.com.br/sobre).',
        ]);

        $pagina = \App\Models\CmsPage::create([
            'title' => 'Sobre', 'slug' => 'sobre', 'is_published' => true,
            'body' => 'Veja [este link](https://exemplo.com.br/x).',
        ]);

        $this->assertStringContainsString('target="_blank"', $vaga->rendered_description);
        $this->assertStringContainsString('target="_blank"', $pagina->rendered_body);
    }

    #[Test]
    public function o_credito_da_fonte_ja_abria_em_outra_aba(): void
    {
        $post = $this->noticia([
            'source_url' => 'https://exemplo.com.br/materia',
            'source_name' => 'Diário dos Campos',
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('target="_blank"', false);
    }
}
