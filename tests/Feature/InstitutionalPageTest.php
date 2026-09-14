<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\User;
use App\Support\InstitutionalPages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * As paginas de rota fixa (Sobre, Benefícios, ...) sao alimentadas pelo CMS
 * atraves do slug. Este vinculo ja quebrou em silencio uma vez: o slug era
 * regerado a partir do titulo a cada gravacao, e a "Sobre" ficou meses
 * mostrando o texto embutido no Blade.
 */
class InstitutionalPageTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Título da página',
            'body' => 'Corpo com tamanho suficiente para passar na validação mínima.',
            'is_published' => '1',
        ], $overrides);
    }

    #[Test]
    public function cada_pagina_institucional_e_alimentada_pelo_cms(): void
    {
        // O teste que faltava: prova que o conteudo do CMS chega na rota fixa.
        foreach (InstitutionalPages::all() as $slug => $meta) {
            $marca = "Conteudo vindo do CMS para {$slug}";

            CmsPage::create([
                'title' => $meta['label'],
                'slug' => $slug,
                'body' => $marca,
                'is_published' => true,
            ]);

            $this->get(route($meta['route']))
                ->assertOk()
                ->assertSee($marca);
        }
    }

    #[Test]
    public function a_pagina_sobre_mostra_missao_visao_e_valores_do_cms(): void
    {
        \App\Models\CmsItem::create([
            'module' => 'missao-visao', 'slug' => 'missao', 'title' => 'Missão',
            'subtitle' => 'O nosso porquê', 'description' => 'Conectar e fortalecer o ecossistema.',
            'position' => 0, 'is_active' => true,
        ]);

        \App\Models\CmsItem::create([
            'module' => 'valores', 'slug' => 'colaboracao', 'title' => 'Colaboração',
            'description' => 'Resultados construídos em conjunto.',
            'position' => 0, 'is_active' => true,
        ]);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('Missão')
            ->assertSee('O nosso porquê')
            ->assertSee('Conectar e fortalecer o ecossistema.')
            ->assertSee('Colaboração')
            ->assertSee('Resultados construídos em conjunto.');
    }

    #[Test]
    public function card_desativado_nao_aparece_na_pagina_sobre(): void
    {
        \App\Models\CmsItem::create([
            'module' => 'valores', 'slug' => 'oculto', 'title' => 'Valor oculto',
            'description' => 'Não deve aparecer no portal.',
            'position' => 0, 'is_active' => false,
        ]);

        $this->get(route('about'))->assertOk()->assertDontSee('Valor oculto');
    }

    #[Test]
    public function pagina_despublicada_nao_alimenta_a_rota(): void
    {
        CmsPage::create([
            'title' => 'Sobre',
            'slug' => 'sobre',
            'body' => 'Texto que nao deve aparecer.',
            'is_published' => false,
        ]);

        $this->get(route('about'))->assertOk()->assertDontSee('Texto que nao deve aparecer.');
    }

    #[Test]
    public function mudar_o_titulo_nao_muda_o_endereco(): void
    {
        // A causa raiz do bug: o slug acompanhava o titulo e desligava a rota.
        $page = CmsPage::create([
            'title' => 'Sobre',
            'slug' => 'sobre',
            'body' => 'Conteúdo institucional da associação.',
            'is_published' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.cms.pages.update', $page), $this->payload([
                'title' => 'Um ponto de encontro para tecnologia em Ponta Grossa',
                'body' => 'Conteúdo institucional atualizado pela diretoria.',
            ]))
            ->assertRedirect();

        $page->refresh();

        $this->assertSame('sobre', $page->slug);
        $this->assertSame('Um ponto de encontro para tecnologia em Ponta Grossa', $page->title);

        // E a rota continua enxergando a pagina.
        $this->get(route('about'))->assertOk()->assertSee('Conteúdo institucional atualizado pela diretoria.');
    }

    #[Test]
    public function o_endereco_muda_quando_o_editor_pede(): void
    {
        $page = CmsPage::create([
            'title' => 'Página comum',
            'slug' => 'pagina-comum',
            'body' => 'Conteúdo qualquer com tamanho suficiente.',
            'is_published' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.cms.pages.update', $page), $this->payload([
                'title' => 'Página comum',
                'slug' => 'outro-endereco',
            ]))
            ->assertRedirect();

        $this->assertSame('outro-endereco', $page->fresh()->slug);
    }

    #[Test]
    public function pagina_nova_ganha_endereco_a_partir_do_titulo(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.cms.pages.store'), $this->payload(['title' => 'Comitês e Grupos']))
            ->assertRedirect();

        $this->assertSame('comites-e-grupos', CmsPage::latest('id')->first()->slug);
    }

    #[Test]
    public function endereco_repetido_e_recusado(): void
    {
        CmsPage::create([
            'title' => 'Sobre',
            'slug' => 'sobre',
            'body' => 'Conteúdo institucional da associação.',
            'is_published' => true,
        ]);

        $outra = CmsPage::create([
            'title' => 'Outra',
            'slug' => 'outra',
            'body' => 'Conteúdo qualquer com tamanho suficiente.',
            'is_published' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.cms.pages.update', $outra), $this->payload([
                'title' => 'Outra',
                'slug' => 'sobre',
            ]))
            ->assertSessionHasErrors('slug');
    }

    #[Test]
    public function endereco_com_formato_invalido_e_recusado(): void
    {
        $page = CmsPage::create([
            'title' => 'Outra',
            'slug' => 'outra',
            'body' => 'Conteúdo qualquer com tamanho suficiente.',
            'is_published' => true,
        ]);

        $this->actingAs($this->admin())
            ->put(route('admin.cms.pages.update', $page), $this->payload([
                'title' => 'Outra',
                'slug' => 'Endereço Com Espaços',
            ]))
            ->assertSessionHasErrors('slug');
    }

    #[Test]
    public function o_painel_avisa_que_a_pagina_e_institucional(): void
    {
        $page = CmsPage::create([
            'title' => 'Sobre',
            'slug' => 'sobre',
            'body' => 'Conteúdo institucional da associação.',
            'is_published' => true,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.cms.pages.edit', $page))
            ->assertOk()
            ->assertSee('Página institucional', false)
            ->assertSee(route('about'));
    }
}
