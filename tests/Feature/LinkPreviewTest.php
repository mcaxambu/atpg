<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Support\LinkPreview;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Busca de titulo, resumo e capa a partir do link da materia.
 *
 * A parte critica e a trava de SSRF: quem digita a URL e o usuario do painel,
 * mas quem faz a requisicao e o SERVIDOR. Sem trava, bastaria apontar para um
 * endereco da rede interna para o servidor buscar por dentro e devolver o
 * conteudo na tela do painel.
 */
class LinkPreviewTest extends TestCase
{
    use RefreshDatabase;

    private const HTML = <<<'HTML'
        <html><head>
            <title>Título da tag title</title>
            <meta property="og:title" content="Startup de PG capta investimento">
            <meta property="og:description" content="Empresa da cidade recebe aporte para expandir operação.">
            <meta property="og:image" content="https://exemplo.com.br/capa.jpg">
            <meta property="og:site_name" content="Diário dos Campos">
        </head><body>conteúdo</body></html>
        HTML;

    private function admin(): User
    {
        return User::factory()->create();
    }

    /**
     * Buscador com DNS falso: "exemplo.com.br" nao existe de verdade, e a
     * checagem de IP roda ANTES da requisicao — entao Http::fake sozinho nao
     * chegaria a ser exercitado.
     *
     * O mapa imita a realidade em vez de liberar tudo: um resolvedor que
     * devolve IP publico para qualquer nome escondia justamente os casos que
     * este teste existe para pegar (como "localhost").
     */
    private function preview(): LinkPreview
    {
        return new LinkPreview(fn (string $host) => match ($host) {
            'exemplo.com.br' => ['93.184.216.34'],
            'localhost' => ['127.0.0.1'],
            default => [],
        });
    }

    /**
     * O endpoint resolve o buscador pelo container; aqui o container passa a
     * devolver o de DNS falso.
     */
    private function fingirDns(): void
    {
        $this->app->bind(LinkPreview::class, fn () => $this->preview());
    }

    // ----- Leitura das metatags -----

    #[Test]
    public function le_titulo_resumo_e_imagem_das_metatags(): void
    {
        Http::fake(['https://exemplo.com.br/*' => Http::response(self::HTML, 200, ['Content-Type' => 'text/html'])]);

        $dados = $this->preview()->fetch('https://exemplo.com.br/materia');

        $this->assertTrue($dados['ok']);
        $this->assertSame('Startup de PG capta investimento', $dados['title']);
        $this->assertSame('Empresa da cidade recebe aporte para expandir operação.', $dados['description']);
        $this->assertSame('https://exemplo.com.br/capa.jpg', $dados['image']);
        $this->assertSame('Diário dos Campos', $dados['site']);
    }

    #[Test]
    public function cai_para_a_tag_title_quando_nao_ha_open_graph(): void
    {
        Http::fake(['https://exemplo.com.br/*' => Http::response(
            '<html><head><title>Só o title</title><meta name="description" content="Resumo comum."></head></html>',
            200, ['Content-Type' => 'text/html']
        )]);

        $dados = $this->preview()->fetch('https://exemplo.com.br/materia');

        $this->assertSame('Só o title', $dados['title']);
        $this->assertSame('Resumo comum.', $dados['description']);
    }

    #[Test]
    public function imagem_relativa_vira_endereco_absoluto(): void
    {
        Http::fake(['https://exemplo.com.br/*' => Http::response(
            '<html><head><meta property="og:image" content="/img/capa.jpg"></head></html>',
            200, ['Content-Type' => 'text/html']
        )]);

        $dados = $this->preview()->fetch('https://exemplo.com.br/materia');

        $this->assertSame('https://exemplo.com.br/img/capa.jpg', $dados['image']);
    }

    // ----- Trava de SSRF -----

    /**
     * @return array<int, array{0: string}>
     */
    public static function enderecosInternos(): array
    {
        return [
            ['http://127.0.0.1/'],
            ['http://localhost/'],
            ['http://169.254.169.254/latest/meta-data/'],
            ['http://192.168.10.100/'],
            ['http://10.0.0.5/admin'],
            ['http://172.16.0.9/'],
            ['http://[::1]/'],
        ];
    }

    #[Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('enderecosInternos')]
    public function recusa_endereco_da_rede_interna(string $url): void
    {
        Http::fake();

        $dados = $this->preview()->fetch($url);

        $this->assertFalse($dados['ok'], "Deveria ter recusado: {$url}");

        // O importante: nem chegou a fazer a requisicao.
        Http::assertNothingSent();
    }

    #[Test]
    public function recusa_esquema_que_nao_seja_http(): void
    {
        Http::fake();

        foreach (['file:///etc/passwd', 'ftp://exemplo.com.br/x', 'gopher://exemplo.com.br'] as $url) {
            $this->assertFalse($this->preview()->fetch($url)['ok'], "Deveria ter recusado: {$url}");
        }

        Http::assertNothingSent();
    }

    #[Test]
    public function redirecionamento_para_a_rede_interna_e_barrado(): void
    {
        // O jeito classico de furar a checagem: o endereco publico responde
        // 302 apontando para dentro.
        Http::fake([
            'https://exemplo.com.br/*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/']),
        ]);

        $dados = $this->preview()->fetch('https://exemplo.com.br/redireciona');

        $this->assertFalse($dados['ok']);
        $this->assertStringContainsString('rede interna', $dados['error']);
    }

    #[Test]
    public function recusa_conteudo_que_nao_e_pagina_web(): void
    {
        Http::fake(['https://exemplo.com.br/*' => Http::response('binario', 200, ['Content-Type' => 'application/pdf'])]);

        $this->assertFalse($this->preview()->fetch('https://exemplo.com.br/arquivo.pdf')['ok']);
    }

    // ----- Endpoint do painel -----

    #[Test]
    public function o_painel_devolve_a_previa_do_link(): void
    {
        Http::fake(['https://exemplo.com.br/*' => Http::response(self::HTML, 200, ['Content-Type' => 'text/html'])]);
        $this->fingirDns();

        $this->actingAs($this->admin())
            ->postJson(route('admin.cms.posts.preview'), ['url' => 'https://exemplo.com.br/materia'])
            ->assertOk()
            ->assertJson(['ok' => true, 'title' => 'Startup de PG capta investimento']);
    }

    #[Test]
    public function o_endpoint_de_previa_exige_login(): void
    {
        Http::fake();

        // O painel manda o visitante para a tela de acesso; o que importa e
        // que a requisicao externa nao aconteceu.
        $this->postJson(route('admin.cms.posts.preview'), ['url' => 'https://exemplo.com.br/materia'])
            ->assertRedirect();

        Http::assertNothingSent();
    }

    #[Test]
    public function admin_sem_o_modulo_cms_nao_usa_a_previa(): void
    {
        Http::fake();

        $restrito = User::factory()->create([
            'role' => \App\Enums\UserRole::Admin,
            'abilities' => ['members'],
        ]);

        $this->actingAs($restrito)
            ->postJson(route('admin.cms.posts.preview'), ['url' => 'https://exemplo.com.br/materia'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    // ----- Fonte na notícia publicada -----

    #[Test]
    public function a_noticia_mostra_o_credito_da_fonte(): void
    {
        $post = Post::create([
            'title' => 'Startup capta investimento',
            'slug' => 'startup-capta-investimento',
            'excerpt' => 'Resumo.',
            'body' => 'Texto da notícia com tamanho suficiente.',
            'category' => 'Notícias',
            'source_url' => 'https://exemplo.com.br/materia',
            'source_name' => 'Diário dos Campos',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('posts.show', $post))
            ->assertOk()
            ->assertSee('Diário dos Campos')
            ->assertSee('https://exemplo.com.br/materia', false)
            ->assertSee('nofollow', false);
    }

    #[Test]
    public function noticia_sem_fonte_nao_mostra_o_bloco_de_credito(): void
    {
        $post = Post::create([
            'title' => 'Notícia própria',
            'slug' => 'noticia-propria',
            'excerpt' => 'Resumo.',
            'body' => 'Texto da notícia com tamanho suficiente.',
            'category' => 'Notícias',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('posts.show', $post))->assertOk()->assertDontSee('Fonte:');
    }
}
