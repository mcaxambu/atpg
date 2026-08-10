<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Member;
use App\Models\Post;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicPortalTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A listagem de noticias destaca a mais recente na primeira pagina. Nas
     * seguintes a grade e uniforme — um "destaque" por pagina nao destacaria
     * nada.
     */
    #[Test]
    public function a_noticia_mais_recente_vira_destaque_so_na_primeira_pagina(): void
    {
        foreach (range(1, 11) as $i) {
            Post::create([
                'title' => "Notícia {$i}",
                'slug' => "noticia-{$i}",
                'excerpt' => 'Resumo.',
                'body' => 'Conteúdo.',
                'category' => 'Notícias',
                'is_published' => true,
                'published_at' => now()->subDays(20 - $i),
            ]);
        }

        $this->get(route('posts.index'))
            ->assertOk()
            ->assertSee('post-featured', false)
            ->assertSee('Notícia 11');

        $this->get(route('posts.index', ['page' => 2]))
            ->assertOk()
            ->assertDontSee('post-featured', false);
    }

    /**
     * O corpo da noticia e Markdown. Antes era nl2br puro: subtitulo e lista
     * saiam como linhas soltas separadas por <br>.
     */
    #[Test]
    public function o_corpo_da_noticia_e_renderizado_como_markdown(): void
    {
        $post = Post::create([
            'title' => 'Com estrutura',
            'slug' => 'com-estrutura',
            'excerpt' => 'Resumo.',
            'body' => '## Pilares

- Primeiro
- Segundo

Texto com **destaque**.',
            'category' => 'Notícias',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('<h2>Pilares</h2>', false)
            ->assertSee('<li>Primeiro</li>', false)
            ->assertSee('<strong>destaque</strong>', false);
    }

    /**
     * Conteudo de administrador tambem passa pelo escape: uma conta
     * comprometida nao pode virar XSS armazenado para todo visitante.
     */
    #[Test]
    public function html_cru_na_noticia_nao_e_executado(): void
    {
        $post = Post::create([
            'title' => 'Com script',
            'slug' => 'com-script',
            'excerpt' => 'Resumo.',
            'body' => 'Antes <script>alert(1)</script> depois.',
            'category' => 'Notícias',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;', false);
    }

    #[Test]
    public function todas_as_paginas_publicas_respondem(): void
    {
        Specialty::factory()->create();
        $company = Company::factory()->create();
        Member::factory()->for($company)->create();

        $urls = [
            route('home'),
            route('members.index'),
            route('companies.index'),
            route('events'),
            route('posts.index'),
            route('about'),
            route('join'),
            route('projects'),
            route('benefits'),
            route('governance'),
            route('lgpd'),
            route('privacy'),
            route('cookies'),
            route('members.create'),
            route('companies.register.create'),
            route('sitemap'),
            route('admin.login'),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk("Falhou em {$url}");
        }
    }

    #[Test]
    public function o_portal_tambem_responde_sob_o_prefixo_atpg(): void
    {
        foreach (['/atpg', '/atpg/empresas', '/atpg/membros', '/atpg/admin/login'] as $url) {
            $this->assertSame(200, $this->get($url)->getStatusCode(), "Falhou em {$url}");
        }
    }

    #[Test]
    public function o_sitemap_lista_apenas_conteudo_publicado(): void
    {
        $published = Company::factory()->create(['slug' => 'empresa-publicada']);
        $pending = Company::factory()->pending()->create(['slug' => 'empresa-pendente']);

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee($published->slug)
            ->assertDontSee($pending->slug);
    }

    #[Test]
    public function a_busca_do_diretorio_filtra_empresas(): void
    {
        Company::factory()->create(['name' => 'Alpha Digital', 'segment' => 'Software']);
        Company::factory()->create(['name' => 'Beta Sistemas', 'segment' => 'Dados']);

        $this->get(route('companies.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Digital')
            ->assertDontSee('Beta Sistemas');

        $this->get(route('companies.index', ['segment' => 'Dados']))
            ->assertOk()
            ->assertSee('Beta Sistemas')
            ->assertDontSee('Alpha Digital');
    }
}
