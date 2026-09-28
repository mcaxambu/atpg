<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Onde o texto rico aparece FORA da pagina que o exibe formatado.
 *
 * Os campos longos guardam HTML desde a virada do editor. Toda tela que
 * imprime um desses campos como texto simples passou a mostrar as tags — foi
 * o que aconteceu no cartao do diretorio de empresas ("<p>Consultoria em...").
 * Os testes aqui existem para prender essa classe de defeito nos lugares em
 * que ela cabe: cartao, painel e API.
 */
class RichTextDisplayTest extends TestCase
{
    use RefreshDatabase;

    private const DESCRICAO = '<p>Consultoria em <strong>gestão</strong> empresarial.</p><p>Segundo parágrafo.</p>';

    // ----- Diretorio publico -----

    #[Test]
    public function o_cartao_da_empresa_mostra_texto_e_nao_as_tags(): void
    {
        Company::factory()->create([
            'name' => 'Amex Gestão',
            'description' => self::DESCRICAO,
        ]);

        $resposta = $this->get(route('companies.index'))->assertOk();

        $resposta->assertSee('Consultoria em gestão empresarial.', false);
        $resposta->assertDontSee('&lt;p&gt;', false);
        $resposta->assertDontSee('&lt;strong&gt;', false);
    }

    #[Test]
    public function a_pagina_da_empresa_continua_exibindo_a_formatacao(): void
    {
        // O oposto do teste acima: aqui o HTML e para valer.
        $company = Company::factory()->create(['description' => self::DESCRICAO]);

        $this->get(route('companies.show', $company->slug))
            ->assertOk()
            ->assertSee('<strong>gestão</strong>', false);
    }

    // ----- Painel da diretoria -----

    #[Test]
    public function a_analise_da_empresa_mostra_o_texto_formatado(): void
    {
        $company = Company::factory()->create(['description' => self::DESCRICAO]);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)
            ->get(route('admin.companies.show', $company))
            ->assertOk()
            ->assertSee('<strong>gestão</strong>', false)
            ->assertDontSee('&lt;p&gt;', false);
    }

    #[Test]
    public function a_analise_do_membro_mostra_o_texto_formatado(): void
    {
        $member = Member::factory()->create(['summary' => '<p>Dez anos em <em>dados</em>.</p>']);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)
            ->get(route('admin.members.show', $member))
            ->assertOk()
            ->assertSee('<em>dados</em>', false)
            ->assertDontSee('&lt;p&gt;', false);
    }

    // ----- API publica -----

    #[Test]
    public function a_api_entrega_a_descricao_sem_tags_e_inteira(): void
    {
        $company = Company::factory()->create(['description' => self::DESCRICAO]);

        $descricao = $this->getJson(route('api.v1.companies.show', $company->slug))
            ->assertOk()
            ->json('data.descricao');

        $this->assertStringNotContainsString('<', $descricao);
        $this->assertStringContainsString('Consultoria em gestão empresarial.', $descricao);

        // Inteira: o segundo paragrafo nao pode ficar de fora, como ficaria se
        // a API usasse o resumo cortado do cartao.
        $this->assertStringContainsString('Segundo parágrafo.', $descricao);
    }

    #[Test]
    public function a_api_entrega_o_resumo_do_membro_sem_tags(): void
    {
        $member = Member::factory()->create(['summary' => '<p>Dez anos em <em>dados</em>.</p>']);

        $resumo = $this->getJson(route('api.v1.members.show', $member->slug))
            ->assertOk()
            ->json('data.resumo');

        $this->assertSame('Dez anos em dados.', $resumo);
    }

    // ----- Texto antigo, ainda em Markdown -----

    #[Test]
    public function conteudo_antigo_em_markdown_tambem_sai_limpo_no_cartao(): void
    {
        // Ha registros que nunca passaram pela conversao (rascunho antigo,
        // importacao). O cartao nao pode mostrar os asteriscos.
        Company::factory()->create(['description' => 'Consultoria em **gestão** empresarial.']);

        $this->get(route('companies.index'))
            ->assertOk()
            ->assertSee('Consultoria em gestão empresarial.', false)
            ->assertDontSee('**gestão**', false);
    }
}
