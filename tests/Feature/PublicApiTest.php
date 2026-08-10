<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Member;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lista_empresas_publicadas(): void
    {
        Company::factory()->create(['name' => 'Alpha Digital']);
        Company::factory()->pending()->create(['name' => 'Beta Pendente']);
        Company::factory()->rejected()->create(['name' => 'Gama Rejeitada']);

        $response = $this->getJson('/api/v1/empresas')->assertOk();

        $nomes = collect($response->json('data'))->pluck('nome');

        $this->assertContains('Alpha Digital', $nomes);
        $this->assertNotContains('Beta Pendente', $nomes);
        $this->assertNotContains('Gama Rejeitada', $nomes);

        $response->assertJsonStructure([
            'data' => [['slug', 'nome', 'segmento', 'cidade', 'total_membros', 'url']],
            'links', 'meta',
        ]);
    }

    #[Test]
    public function detalhe_da_empresa_traz_os_membros(): void
    {
        $company = Company::factory()->create(['slug' => 'alpha-digital']);
        Member::factory()->for($company)->create(['name' => 'Ana Souza']);
        Member::factory()->for($company)->pending()->create(['name' => 'Oculto Pendente']);

        $response = $this->getJson('/api/v1/empresas/alpha-digital')->assertOk();

        $membros = collect($response->json('data.membros'))->pluck('nome');

        $this->assertContains('Ana Souza', $membros);
        $this->assertNotContains('Oculto Pendente', $membros);
    }

    #[Test]
    public function empresa_nao_publicada_da_404(): void
    {
        $company = Company::factory()->pending()->create(['slug' => 'oculta']);

        $this->getJson('/api/v1/empresas/oculta')->assertNotFound();
        $this->assertNotNull($company->fresh());
    }

    #[Test]
    public function lista_membros_e_filtra_por_especialidade(): void
    {
        $specialty = Specialty::factory()->create(['slug' => 'devops', 'name' => 'DevOps']);
        $comEspecialidade = Member::factory()->create(['name' => 'Com DevOps']);
        $comEspecialidade->specialties()->attach($specialty);
        Member::factory()->create(['name' => 'Sem DevOps']);

        $nomes = collect($this->getJson('/api/v1/membros?especialidade=devops')->assertOk()->json('data'))
            ->pluck('nome');

        $this->assertContains('Com DevOps', $nomes);
        $this->assertNotContains('Sem DevOps', $nomes);
    }

    #[Test]
    public function traz_links_prontos_para_a_empresa_e_para_a_propria_api(): void
    {
        $company = Company::factory()->create(['slug' => 'alpha-digital']);
        Member::factory()->for($company)->create(['slug' => 'ana-souza']);

        $empresa = $this->getJson('/api/v1/empresas')->assertOk()->json('data.0');

        $this->assertStringEndsWith('/empresas/alpha-digital', $empresa['url']);
        $this->assertStringEndsWith('/api/v1/empresas/alpha-digital', $empresa['api_url']);

        $membro = $this->getJson('/api/v1/membros')->assertOk()->json('data.0');

        $this->assertStringEndsWith('/membros/ana-souza', $membro['url']);
        $this->assertStringEndsWith('/api/v1/membros/ana-souza', $membro['api_url']);

        // O vinculo com a empresa vem navegavel, sem precisar montar URL.
        $this->assertStringEndsWith('/empresas/alpha-digital', $membro['empresa']['url']);
        $this->assertStringEndsWith('/api/v1/empresas/alpha-digital', $membro['empresa']['api_url']);
    }

    #[Test]
    public function a_api_nao_expoe_contato_pessoal(): void
    {
        Member::factory()->create([
            'name' => 'Ana Souza',
            'email' => 'ana@exemplo.test',
            'whatsapp' => '(42) 99999-9999',
        ]);

        $lista = $this->getJson('/api/v1/membros')->assertOk();
        $lista->assertDontSee('ana@exemplo.test');
        $lista->assertDontSee('99999-9999');

        $detalhe = $this->getJson('/api/v1/membros/'.Member::first()->slug)->assertOk();
        $detalhe->assertDontSee('ana@exemplo.test');
    }

    #[Test]
    public function membro_de_empresa_nao_publicada_fica_fora(): void
    {
        $company = Company::factory()->pending()->create();
        Member::factory()->for($company)->create(['name' => 'Invisivel']);

        $nomes = collect($this->getJson('/api/v1/membros')->assertOk()->json('data'))->pluck('nome');

        $this->assertNotContains('Invisivel', $nomes);
    }

    #[Test]
    public function especialidades_trazem_a_contagem_de_membros(): void
    {
        $specialty = Specialty::factory()->create(['slug' => 'devops']);
        Member::factory()->create()->specialties()->attach($specialty);

        $this->getJson('/api/v1/especialidades')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'devops', 'total_membros' => 1]);
    }

    #[Test]
    public function parametros_invalidos_sao_recusados(): void
    {
        $this->getJson('/api/v1/empresas?por_pagina=500')
            ->assertStatus(422)
            ->assertJsonValidationErrors('por_pagina');
    }
}
