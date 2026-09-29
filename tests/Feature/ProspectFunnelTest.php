<?php

namespace Tests\Feature;

use App\Enums\AdminModule;
use App\Enums\InteractionType;
use App\Enums\ProspectStage;
use App\Enums\UserRole;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Funil de novos associados.
 *
 * O que decide se este módulo serve para alguma coisa não é o quadro bonito: é
 * o prospecto atrasado aparecer, o histórico contar a conversa inteira e o
 * responsável nunca ficar em branco. É isso que está preso aqui.
 */
class ProspectFunnelTest extends TestCase
{
    use RefreshDatabase;

    private function diretoria(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
    }

    // ----- Quem alcança -----

    #[Test]
    public function admin_sem_o_modulo_nao_alcanca_o_funil(): void
    {
        $semAcesso = User::factory()->create([
            'role' => UserRole::Admin,
            'abilities' => [AdminModule::Members->value],
        ]);

        $this->actingAs($semAcesso)->get(route('admin.crm.index'))->assertForbidden();

        $prospecto = Prospect::factory()->create();
        $this->actingAs($semAcesso)->get(route('admin.crm.show', $prospecto))->assertForbidden();
    }

    #[Test]
    public function empresa_e_membro_nao_alcancam_o_funil(): void
    {
        foreach ([UserRole::Company, UserRole::Member] as $papel) {
            $this->actingAs(User::factory()->create(['role' => $papel]))
                ->get(route('admin.crm.index'))
                ->assertRedirect();
        }
    }

    // ----- Cadastro -----

    #[Test]
    public function cadastra_um_prospecto_e_registra_a_entrada_no_historico(): void
    {
        $user = $this->diretoria();

        $this->actingAs($user)->post(route('admin.crm.store'), [
            'name' => 'Dataholds',
            'kind' => Prospect::KIND_COMPANY,
            'contact_name' => 'Rodrigo',
            'stage' => ProspectStage::Contato->value,
            'source' => 'indicacao',
            'next_action' => 'Marcar café',
            'next_action_at' => now()->addWeek()->toDateString(),
        ])->assertRedirect();

        $prospecto = Prospect::firstOrFail();

        $this->assertSame('Dataholds', $prospecto->name);
        $this->assertSame(ProspectStage::Contato, $prospecto->stage);

        // Sem responsável escolhido, fica com quem cadastrou.
        $this->assertSame($user->id, $prospecto->owner_user_id);

        $this->assertCount(1, $prospecto->interactions);
        $this->assertSame(InteractionType::Sistema, $prospecto->interactions->first()->type);
    }

    #[Test]
    public function o_nome_e_obrigatorio(): void
    {
        $this->actingAs($this->diretoria())
            ->post(route('admin.crm.store'), ['kind' => Prospect::KIND_COMPANY, 'stage' => 'novo', 'source' => 'evento'])
            ->assertSessionHasErrors('name');

        $this->assertSame(0, Prospect::count());
    }

    // ----- Etapas -----

    #[Test]
    public function mudar_de_etapa_escreve_a_mudanca_no_historico(): void
    {
        $user = $this->diretoria();
        $prospecto = Prospect::factory()->create(['stage' => ProspectStage::Contato]);

        $this->actingAs($user)
            ->patch(route('admin.crm.stage', $prospecto), ['stage' => ProspectStage::Reuniao->value])
            ->assertRedirect();

        $prospecto->refresh();

        $this->assertSame(ProspectStage::Reuniao, $prospecto->stage);
        $this->assertNotNull($prospecto->stage_changed_at);

        $registro = $prospecto->interactions()->first();
        $this->assertStringContainsString('Em contato', $registro->summary);
        $this->assertStringContainsString('Reunião', $registro->summary);
        $this->assertSame($user->id, $registro->user_id);
    }

    #[Test]
    public function perder_um_prospecto_exige_motivo(): void
    {
        $prospecto = Prospect::factory()->create(['stage' => ProspectStage::Reuniao]);

        $this->actingAs($this->diretoria())
            ->patch(route('admin.crm.stage', $prospecto), ['stage' => ProspectStage::Perdido->value])
            ->assertSessionHasErrors('lost_reason');

        $this->assertSame(ProspectStage::Reuniao, $prospecto->fresh()->stage);
    }

    #[Test]
    public function o_motivo_da_perda_fica_gravado_e_no_historico(): void
    {
        $prospecto = Prospect::factory()->create(['stage' => ProspectStage::Convite]);

        $this->actingAs($this->diretoria())->patch(route('admin.crm.stage', $prospecto), [
            'stage' => ProspectStage::Perdido->value,
            'lost_reason' => 'Vai entrar ano que vem.',
        ])->assertRedirect();

        $prospecto->refresh();

        $this->assertSame(ProspectStage::Perdido, $prospecto->stage);
        $this->assertSame('Vai entrar ano que vem.', $prospecto->lost_reason);
        $this->assertStringContainsString('Vai entrar ano que vem.', $prospecto->interactions()->first()->summary);
    }

    // ----- Contato e próximo passo -----

    #[Test]
    public function registrar_contato_grava_o_proximo_passo_junto(): void
    {
        $prospecto = Prospect::factory()->create(['next_action' => 'Antigo', 'next_action_at' => now()->subDay()]);

        $this->actingAs($this->diretoria())->post(route('admin.crm.interactions.store', $prospecto), [
            'type' => InteractionType::Ligacao->value,
            'summary' => 'Liguei, vai levar para os sócios.',
            'next_action' => 'Retornar na terça',
            'next_action_at' => now()->addDays(5)->toDateString(),
        ])->assertRedirect();

        $prospecto->refresh();

        $this->assertSame('Retornar na terça', $prospecto->next_action);
        $this->assertTrue($prospecto->next_action_at->isFuture());
        $this->assertSame('Liguei, vai levar para os sócios.', $prospecto->interactions->first()->summary);
    }

    #[Test]
    public function registrar_contato_antigo_nao_apaga_o_combinado_que_vale(): void
    {
        // Anotar depois uma conversa da semana passada nao pode derrubar o
        // proximo passo ja marcado.
        $prospecto = Prospect::factory()->create([
            'next_action' => 'Enviar proposta',
            'next_action_at' => now()->addWeek()->toDateString(),
        ]);

        $this->actingAs($this->diretoria())->post(route('admin.crm.interactions.store', $prospecto), [
            'type' => InteractionType::Nota->value,
            'summary' => 'Esqueci de anotar: conversamos no evento.',
            'happened_at' => now()->subWeek()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertSame('Enviar proposta', $prospecto->fresh()->next_action);
    }

    #[Test]
    public function o_contato_nao_pode_estar_no_futuro(): void
    {
        $prospecto = Prospect::factory()->create();

        $this->actingAs($this->diretoria())->post(route('admin.crm.interactions.store', $prospecto), [
            'type' => InteractionType::Ligacao->value,
            'summary' => 'Ligação de amanhã',
            'happened_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('happened_at');
    }

    // ----- O que o funil precisa destacar -----

    #[Test]
    public function o_atrasado_aparece_no_topo_da_tela(): void
    {
        Prospect::factory()->atrasado()->create(['name' => 'Empresa Esquecida']);
        Prospect::factory()->create(['name' => 'Empresa Em Dia', 'next_action_at' => now()->addWeek()]);

        $resposta = $this->actingAs($this->diretoria())->get(route('admin.crm.index'))->assertOk();

        $resposta->assertSee('Precisa de ação');
        $resposta->assertSee('Empresa Esquecida');
        $resposta->assertSeeInOrder(['Precisa de ação', 'Empresa Esquecida']);
    }

    #[Test]
    public function quem_ja_entrou_ou_se_perdeu_sai_do_quadro(): void
    {
        Prospect::factory()->create(['name' => 'Ainda Conversando', 'stage' => ProspectStage::Contato]);
        Prospect::factory()->create(['name' => 'Ja Associada', 'stage' => ProspectStage::Associado]);
        Prospect::factory()->create(['name' => 'Nao Deu Certo', 'stage' => ProspectStage::Perdido]);

        $resposta = $this->actingAs($this->diretoria())->get(route('admin.crm.index'))->assertOk();

        $resposta->assertSee('Ainda Conversando');
        $resposta->assertDontSee('Ja Associada');
        $resposta->assertDontSee('Nao Deu Certo');
    }

    #[Test]
    public function filtrar_por_etapa_mostra_todos_daquela_etapa(): void
    {
        Prospect::factory()->count(3)->create(['stage' => ProspectStage::Associado]);

        $this->actingAs($this->diretoria())
            ->get(route('admin.crm.index', ['etapa' => ProspectStage::Associado->value]))
            ->assertOk()
            ->assertSee('Associado');
    }

    #[Test]
    public function marca_como_parado_quem_nao_anda_ha_semanas(): void
    {
        $parado = Prospect::factory()->parado(30)->create();
        $novo = Prospect::factory()->create();

        $this->assertTrue($parado->parado());
        $this->assertFalse($novo->parado());
    }

    #[Test]
    public function prospecto_associado_nao_conta_como_atrasado(): void
    {
        // Quem ja entrou nao tem proximo passo a cobrar, mesmo com data velha.
        $prospecto = Prospect::factory()->create([
            'stage' => ProspectStage::Associado,
            'next_action_at' => now()->subMonth()->toDateString(),
        ]);

        $this->assertFalse($prospecto->atrasado());
        $this->assertSame(0, Prospect::atrasados()->count());
    }
}
