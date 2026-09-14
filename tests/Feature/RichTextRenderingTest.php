<?php

namespace Tests\Feature;

use App\Models\CmsPage;
use App\Models\Company;
use App\Models\Event;
use App\Models\JobOpening;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Os campos longos do painel passaram a ser escritos num editor visual que
 * grava Markdown. Aqui se garante as duas metades disso: a formatacao aparece
 * no site, e HTML cru continua sem ser executado.
 */
class RichTextRenderingTest extends TestCase
{
    use RefreshDatabase;

    private const MARKDOWN = "## Nosso trabalho\n\nTexto com **negrito**.\n\n- Primeiro\n- Segundo";

    private const SCRIPT = 'Antes <script>alert(1)</script> depois.';

    #[Test]
    public function a_pagina_institucional_mostra_a_formatacao(): void
    {
        CmsPage::create([
            'title' => 'Sobre',
            'slug' => 'sobre',
            'body' => self::MARKDOWN,
            'is_published' => true,
        ]);

        $this->get(route('about'))
            ->assertOk()
            ->assertSee('<h2>Nosso trabalho</h2>', false)
            ->assertSee('<strong>negrito</strong>', false)
            ->assertSee('<li>Primeiro</li>', false);
    }

    #[Test]
    public function a_descricao_da_vaga_mostra_a_formatacao(): void
    {
        $job = JobOpening::factory()->create(['description' => self::MARKDOWN]);

        $this->get(route('vagas.show', $job))
            ->assertOk()
            ->assertSee('<h2>Nosso trabalho</h2>', false)
            ->assertSee('<li>Segundo</li>', false);
    }

    #[Test]
    public function o_perfil_da_empresa_mostra_a_formatacao(): void
    {
        $company = Company::factory()->create(['description' => self::MARKDOWN]);

        $this->get(route('companies.show', $company))
            ->assertOk()
            ->assertSee('<strong>negrito</strong>', false);
    }

    #[Test]
    public function o_perfil_do_membro_mostra_a_formatacao(): void
    {
        $member = Member::factory()->create(['summary' => self::MARKDOWN]);

        $this->get(route('members.show', $member))
            ->assertOk()
            ->assertSee('<strong>negrito</strong>', false);
    }

    #[Test]
    public function o_evento_mostra_a_formatacao(): void
    {
        $event = Event::create([
            'title' => 'Encontro',
            'slug' => 'encontro',
            'description' => self::MARKDOWN,
            'event_date' => now()->addWeek(),
            'is_published' => true,
        ]);

        $this->get(route('events.show', $event))
            ->assertOk()
            ->assertSee('<h2>Nosso trabalho</h2>', false);
    }

    /**
     * Conteudo de administrador tambem passa pelo escape: uma conta
     * comprometida nao pode virar XSS armazenado para todo visitante.
     */
    #[Test]
    public function html_cru_nao_e_executado_em_nenhum_dos_campos(): void
    {
        CmsPage::create([
            'title' => 'Sobre', 'slug' => 'sobre', 'body' => self::SCRIPT, 'is_published' => true,
        ]);

        $evento = Event::create([
            'title' => 'Encontro', 'slug' => 'encontro', 'description' => self::SCRIPT,
            'event_date' => now()->addWeek(), 'is_published' => true,
        ]);

        $urls = [
            'página' => route('about'),
            'vaga' => route('vagas.show', JobOpening::factory()->create(['description' => self::SCRIPT])),
            'empresa' => route('companies.show', Company::factory()->create(['description' => self::SCRIPT])),
            'membro' => route('members.show', Member::factory()->create(['summary' => self::SCRIPT])),
            'evento' => route('events.show', $evento),
        ];

        foreach ($urls as $campo => $url) {
            $resposta = $this->get($url)->assertOk();

            $this->assertStringNotContainsString(
                '<script>alert(1)</script>',
                $resposta->getContent(),
                "O script passou sem escape em: {$campo}"
            );

            // O texto ao redor continua visivel — o escape nao come o conteudo.
            $this->assertStringContainsString('Antes', $resposta->getContent());
        }
    }

    #[Test]
    public function o_resumo_da_listagem_nao_mostra_os_simbolos_do_markdown(): void
    {
        $job = JobOpening::factory()->create(['description' => self::MARKDOWN]);

        $resumo = $job->description_excerpt;

        $this->assertStringNotContainsString('**', $resumo);
        $this->assertStringNotContainsString('##', $resumo);
        $this->assertStringContainsString('negrito', $resumo);

        // E o cartao da listagem usa esse resumo, nao o Markdown cru.
        $this->get(route('vagas.index'))->assertOk()->assertDontSee('**negrito**');
    }

    #[Test]
    public function texto_sem_formatacao_continua_saindo_em_paragrafos(): void
    {
        // O conteudo antigo foi escrito como texto corrido: as quebras de linha
        // precisam continuar aparecendo.
        $page = CmsPage::create([
            'title' => 'Sobre',
            'slug' => 'sobre',
            'body' => "Primeira linha.\nSegunda linha.\n\nOutro parágrafo.",
            'is_published' => true,
        ]);

        $html = $page->rendered_body;

        $this->assertStringContainsString('<br>', $html);
        $this->assertStringContainsString('Outro parágrafo.', $html);
    }
}
