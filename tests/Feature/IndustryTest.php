<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Industry;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Indústrias da região e o cruzamento com o que as associadas fazem.
 *
 * O que estes testes protegem:
 *
 * 1. o cruzamento acerta quem atende, e ordena por tamanho do encaixe;
 * 2. a associada NÃO vê o contato da indústria nem quem mais atende — algumas
 *    delas concorrem entre si;
 * 3. empresa sem especialidade marcada simplesmente não aparece, em vez de
 *    aparecer como se atendesse tudo.
 */
class IndustryTest extends TestCase
{
    use RefreshDatabase;

    private function diretoria(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
    }

    private function especialidade(string $nome): Specialty
    {
        return Specialty::create(['name' => $nome, 'slug' => Specialty::uniqueSlug($nome)]);
    }

    private function industria(array $campos = [], array $precisa = []): Industry
    {
        $industria = Industry::create($campos + [
            'name' => 'Moinho Campos Gerais',
            'slug' => Industry::uniqueSlug($campos['name'] ?? 'Moinho Campos Gerais'),
            'sector' => 'Alimentos',
            'is_active' => true,
        ]);

        $industria->specialties()->sync(collect($precisa)->pluck('id')->all());

        return $industria->load('specialties');
    }

    private function associada(string $nome, array $faz = []): Company
    {
        $empresa = Company::factory()->create(['name' => $nome]);
        $empresa->specialties()->sync(collect($faz)->pluck('id')->all());

        return $empresa->load('specialties');
    }

    // ----- O cruzamento -----

    #[Test]
    public function mostra_quem_atende_e_ordena_pelo_tamanho_do_encaixe(): void
    {
        $automacao = $this->especialidade('Automação');
        $infra = $this->especialidade('Infraestrutura');
        $seo = $this->especialidade('SEO');

        $industria = $this->industria(precisa: [$automacao, $infra]);

        $cobreTudo = $this->associada('Cobre Tudo', [$automacao, $infra]);
        $cobreMetade = $this->associada('Cobre Metade', [$automacao]);
        $this->associada('Faz Outra Coisa', [$seo]);

        $atendem = $industria->quemAtende();

        $this->assertSame(2, $atendem->count());
        // Quem atende mais vem primeiro: ordem alfabética esconderia a
        // resposta que a tela existe para dar.
        $this->assertSame($cobreTudo->id, $atendem->first()->id);
        $this->assertSame($cobreMetade->id, $atendem->last()->id);
    }

    #[Test]
    public function empresa_sem_especialidade_nao_atende_nada(): void
    {
        $automacao = $this->especialidade('Automação');
        $industria = $this->industria(precisa: [$automacao]);

        $this->associada('Sem Nada Marcado');

        $this->assertTrue($industria->quemAtende()->isEmpty());
    }

    #[Test]
    public function industria_sem_necessidade_marcada_nao_cruza_com_ninguem(): void
    {
        $automacao = $this->especialidade('Automação');
        $this->associada('Faz Automação', [$automacao]);

        $industria = $this->industria();

        $this->assertTrue($industria->quemAtende()->isEmpty());
    }

    #[Test]
    public function a_associada_enxerga_em_quais_industrias_se_encaixa(): void
    {
        $automacao = $this->especialidade('Automação');
        $ia = $this->especialidade('Inteligência Artificial');

        $this->industria(['name' => 'Precisa de automação'], [$automacao]);
        $this->industria(['name' => 'Precisa de IA'], [$ia]);

        $empresa = $this->associada('Automatiza Tudo', [$automacao]);

        $encaixes = $empresa->industriasQuePodeAtender();

        $this->assertSame(1, $encaixes->count());
        $this->assertSame('Precisa de automação', $encaixes->first()->name);
    }

    // ----- O que cada um pode ver -----

    #[Test]
    public function a_associada_nao_ve_o_contato_da_industria(): void
    {
        $automacao = $this->especialidade('Automação');
        $industria = $this->industria(['contact_name' => 'Fulano Diretor', 'contact_phone' => '42 99999-0000',
            'notes' => 'Conversa travada desde maio'], [$automacao]);

        $empresa = $this->associada('Automatiza Tudo', [$automacao]);
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $empresa->id]);

        $resposta = $this->actingAs($user)->get(route('painel.industrias.show', $industria->slug))->assertOk();

        $resposta->assertSee('Moinho Campos Gerais');
        $resposta->assertDontSee('Fulano Diretor');
        $resposta->assertDontSee('42 99999-0000');
        $resposta->assertDontSee('Conversa travada desde maio');
    }

    #[Test]
    public function a_associada_nao_ve_quais_outras_associadas_atendem(): void
    {
        // Algumas das associadas concorrem entre si; saber quem mais atende
        // não ajuda ninguém a trabalhar e azeda a convivência.
        $automacao = $this->especialidade('Automação');
        $industria = $this->industria(precisa: [$automacao]);

        $this->associada('Concorrente Direta', [$automacao]);
        $minha = $this->associada('Minha Empresa', [$automacao]);
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $minha->id]);

        $this->actingAs($user)->get(route('painel.industrias.show', $industria->slug))
            ->assertOk()
            ->assertDontSee('Concorrente Direta');
    }

    #[Test]
    public function a_diretoria_ve_o_contato_e_quem_atende(): void
    {
        $automacao = $this->especialidade('Automação');
        $industria = $this->industria(['contact_name' => 'Fulano Diretor'], [$automacao]);
        $this->associada('Automatiza Tudo', [$automacao]);

        $this->actingAs($this->diretoria())->get(route('admin.industrias.show', $industria))
            ->assertOk()
            ->assertSee('Fulano Diretor')
            ->assertSee('Automatiza Tudo');
    }

    #[Test]
    public function industria_inativa_some_para_o_associado(): void
    {
        $industria = $this->industria(['is_active' => false]);
        $empresa = $this->associada('Qualquer Uma');
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $empresa->id]);

        $this->actingAs($user)->get(route('painel.industrias.show', $industria->slug))->assertNotFound();
    }

    #[Test]
    public function quem_nao_entrou_nao_ve_industria_nenhuma(): void
    {
        $this->industria();

        $this->get(route('painel.industrias.index'))->assertRedirect(route('entrar'));
    }

    // ----- Cadastro -----

    #[Test]
    public function a_diretoria_cadastra_industria_com_as_necessidades(): void
    {
        $automacao = $this->especialidade('Automação');

        $this->actingAs($this->diretoria())
            ->post(route('admin.industrias.store'), [
                'name' => 'Cervejaria Princesa',
                'sector' => 'Bebidas',
                'size' => 'media',
                'especialidades' => [$automacao->id],
            ])
            ->assertRedirect();

        $industria = Industry::where('name', 'Cervejaria Princesa')->firstOrFail();

        $this->assertSame('Bebidas', $industria->sector);
        $this->assertTrue($industria->specialties->contains($automacao));
    }

    #[Test]
    public function a_empresa_nao_entra_no_cadastro_de_industrias(): void
    {
        $empresa = $this->associada('Curiosa');
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $empresa->id]);

        $this->actingAs($user)->get(route('admin.industrias.index'))->assertRedirect();
    }

    #[Test]
    public function a_empresa_marca_as_proprias_especialidades_no_painel(): void
    {
        $automacao = $this->especialidade('Automação');
        $empresa = $this->associada('Automatiza Tudo');
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $empresa->id]);

        $this->actingAs($user)
            ->put(route('empresa.perfil.update'), [
                'name' => $empresa->name,
                'segment' => 'Automação industrial',
                'especialidades' => [$automacao->id],
            ])
            ->assertRedirect();

        $this->assertTrue($empresa->fresh()->specialties->contains($automacao));
    }
}
