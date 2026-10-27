<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Check-in de presença nas reuniões.
 *
 * O que sustenta a ideia: o código mostrado NA SALA. Sem ele, qualquer
 * associado logado faria check-in de casa e a presença deixaria de significar
 * presença — é o teste negativo mais importante daqui.
 */
class MeetingCheckInTest extends TestCase
{
    use RefreshDatabase;

    private function reuniao(?\Carbon\Carbon $quando = null): Meeting
    {
        return Meeting::create([
            'title' => 'Assembleia de novembro',
            'type' => MeetingType::AssembleiaOrdinaria,
            'status' => MeetingStatus::Agendada,
            'scheduled_at' => $quando ?? now(),
            'created_by' => null,
        ]);
    }

    /** @return array{0: Company, 1: User, 2: MeetingAttendance} */
    private function convocada(Meeting $reuniao): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $company->id]);

        $presenca = MeetingAttendance::create([
            'meeting_id' => $reuniao->id,
            'company_id' => $company->id,
            'status' => AttendanceStatus::Confirmado,
        ]);

        return [$company, $user, $presenca];
    }

    // ----- O código é o que prova presença -----

    #[Test]
    public function sem_o_codigo_certo_nao_ha_check_in(): void
    {
        $reuniao = $this->reuniao();
        [, $user, $presenca] = $this->convocada($reuniao);

        $this->actingAs($user)
            ->post(route('empresa.reunioes.checkin', $reuniao), ['codigo' => 'XX000'])
            ->assertSessionHasErrors('codigo');

        $presenca->refresh();

        $this->assertNull($presenca->checked_in_at);
        $this->assertNotTrue($presenca->attended);
    }

    #[Test]
    public function com_o_codigo_da_sala_a_presenca_fica_registrada(): void
    {
        $reuniao = $this->reuniao();
        [, $user, $presenca] = $this->convocada($reuniao);

        $this->actingAs($user)
            ->post(route('empresa.reunioes.checkin', $reuniao), ['codigo' => $reuniao->codigoDeCheckin()])
            ->assertRedirect();

        $presenca->refresh();

        $this->assertTrue((bool) $presenca->attended);
        $this->assertNotNull($presenca->checked_in_at);
        $this->assertSame(MeetingAttendance::CHECKIN_EMPRESA, $presenca->checked_in_source);
    }

    #[Test]
    public function o_codigo_nao_diferencia_maiuscula_de_minuscula(): void
    {
        // Ele é digitado no celular, às pressas, lido de um projetor.
        $reuniao = $this->reuniao();
        [, $user, $presenca] = $this->convocada($reuniao);

        $this->actingAs($user)->post(route('empresa.reunioes.checkin', $reuniao), [
            'codigo' => strtolower($reuniao->codigoDeCheckin()),
        ]);

        $this->assertNotNull($presenca->fresh()->checked_in_at);
    }

    // ----- A janela -----

    #[Test]
    public function reuniao_de_outro_dia_nao_aceita_check_in(): void
    {
        $semana = $this->reuniao(now()->addWeek());
        [, $user, $presenca] = $this->convocada($semana);

        $this->assertFalse($semana->checkinAberto());

        $this->actingAs($user)
            ->post(route('empresa.reunioes.checkin', $semana), ['codigo' => $semana->codigoDeCheckin()])
            ->assertSessionHasErrors('codigo');

        $this->assertNull($presenca->fresh()->checked_in_at);
    }

    #[Test]
    public function a_janela_abre_antes_e_fecha_depois_da_reuniao(): void
    {
        // Gente chega cedo, e quem esqueceu na hora ainda registra depois.
        $this->assertTrue($this->reuniao(now()->addMinutes(30))->checkinAberto());
        $this->assertTrue($this->reuniao(now()->subHours(2))->checkinAberto());
        $this->assertFalse($this->reuniao(now()->subDay())->checkinAberto());
        $this->assertFalse($this->reuniao(now()->addHours(5))->checkinAberto());
    }

    #[Test]
    public function reuniao_cancelada_nao_aceita_check_in(): void
    {
        $reuniao = $this->reuniao();
        $reuniao->update(['status' => MeetingStatus::Cancelada]);

        $this->assertFalse($reuniao->fresh()->checkinAberto());
    }

    // ----- Quem pode -----

    #[Test]
    public function empresa_fora_da_lista_nao_faz_check_in(): void
    {
        $reuniao = $this->reuniao();
        $this->convocada($reuniao);

        $outra = Company::factory()->create();
        $visitante = User::factory()->create(['role' => UserRole::Company, 'company_id' => $outra->id]);

        $this->actingAs($visitante)
            ->post(route('empresa.reunioes.checkin', $reuniao), ['codigo' => $reuniao->codigoDeCheckin()])
            ->assertSessionHasErrors('codigo');

        $this->assertSame(0, $reuniao->attendances()->whereNotNull('checked_in_at')->count());
    }

    #[Test]
    public function check_in_repetido_nao_duplica_nem_muda_a_hora(): void
    {
        $reuniao = $this->reuniao();
        [, $user, $presenca] = $this->convocada($reuniao);

        $this->actingAs($user)->post(route('empresa.reunioes.checkin', $reuniao), ['codigo' => $reuniao->codigoDeCheckin()]);
        $primeira = $presenca->fresh()->checked_in_at;

        $this->travel(10)->minutes();

        $this->actingAs($user)->post(route('empresa.reunioes.checkin', $reuniao), ['codigo' => $reuniao->codigoDeCheckin()]);

        $this->assertEquals($primeira, $presenca->fresh()->checked_in_at);
    }

    // ----- A recepção -----

    #[Test]
    public function a_diretoria_marca_chegada_sem_codigo_e_desfaz_no_mesmo_clique(): void
    {
        $reuniao = $this->reuniao();
        [$company, , $presenca] = $this->convocada($reuniao);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)
            ->post(route('admin.reunioes.checkin', $reuniao), ['company_id' => $company->id])
            ->assertRedirect();

        $presenca->refresh();
        $this->assertTrue((bool) $presenca->attended);
        $this->assertSame(MeetingAttendance::CHECKIN_DIRETORIA, $presenca->checked_in_source);

        // Erro de clique na porta precisa ser reversível na hora.
        $this->actingAs($admin)->post(route('admin.reunioes.checkin', $reuniao), ['company_id' => $company->id]);

        $presenca->refresh();
        $this->assertFalse((bool) $presenca->attended);
        $this->assertNull($presenca->checked_in_at);
    }

    #[Test]
    public function a_empresa_nao_marca_chegada_pela_tela_da_diretoria(): void
    {
        $reuniao = $this->reuniao();
        [$company, $user] = $this->convocada($reuniao);

        $this->actingAs($user)
            ->post(route('admin.reunioes.checkin', $reuniao), ['company_id' => $company->id])
            ->assertRedirect(route('empresa.dashboard'));
    }

    // ----- Reunião online -----

    #[Test]
    public function entrar_na_videochamada_pelo_portal_registra_a_presenca(): void
    {
        $reuniao = $this->reuniao();
        $reuniao->update(['online_url' => 'https://meet.google.com/abc-defg-hij']);
        [, $user, $presenca] = $this->convocada($reuniao);

        $this->actingAs($user)
            ->get(route('empresa.reunioes.entrar', $reuniao))
            ->assertRedirect('https://meet.google.com/abc-defg-hij');

        $presenca->refresh();

        $this->assertTrue((bool) $presenca->attended);
        $this->assertSame(MeetingAttendance::CHECKIN_ONLINE, $presenca->checked_in_source);
    }

    #[Test]
    public function a_reuniao_online_nao_pede_codigo(): void
    {
        // Ninguém está numa sala: o código não teria o que provar.
        $reuniao = $this->reuniao();
        $reuniao->update(['online_url' => 'https://meet.google.com/abc-defg-hij']);
        [, $user, $presenca] = $this->convocada($reuniao);

        $this->actingAs($user)->get(route('empresa.reunioes.entrar', $reuniao));

        $this->assertNotNull($presenca->fresh()->checked_in_at);
    }

    #[Test]
    public function fora_da_janela_a_pessoa_entra_na_chamada_sem_registrar(): void
    {
        /*
            Perder a reunião por causa do nosso controle interno seria trocar
            um problema pequeno por um grande: o link sempre leva à chamada.
        */
        $reuniao = $this->reuniao(now()->addWeek());
        $reuniao->update(['online_url' => 'https://meet.google.com/abc-defg-hij']);
        [, $user, $presenca] = $this->convocada($reuniao);

        $this->actingAs($user)
            ->get(route('empresa.reunioes.entrar', $reuniao))
            ->assertRedirect('https://meet.google.com/abc-defg-hij');

        $this->assertNull($presenca->fresh()->checked_in_at);
    }

    #[Test]
    public function reuniao_sem_link_online_nao_tem_essa_porta(): void
    {
        $reuniao = $this->reuniao();
        [, $user] = $this->convocada($reuniao);

        $this->actingAs($user)->get(route('empresa.reunioes.entrar', $reuniao))->assertNotFound();
    }

    // ----- A recepção no tablet -----

    #[Test]
    public function a_recepcao_lista_as_empresas_e_mostra_o_codigo(): void
    {
        $reuniao = $this->reuniao();
        [$company] = $this->convocada($reuniao);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)->get(route('admin.reunioes.recepcao', $reuniao))
            ->assertOk()
            ->assertSee($company->name)
            ->assertSee('toque para marcar a chegada')
            ->assertSee($reuniao->fresh()->codigoDeCheckin());
    }

    #[Test]
    public function a_empresa_nao_abre_a_recepcao(): void
    {
        $reuniao = $this->reuniao();
        [, $user] = $this->convocada($reuniao);

        $this->actingAs($user)->get(route('admin.reunioes.recepcao', $reuniao))->assertRedirect();
    }

    // ----- O QR code -----

    #[Test]
    public function o_qr_encontra_sozinho_a_reuniao_aberta(): void
    {
        // O cartaz é plastificado: aponta para um endereço fixo, e quem
        // descobre qual é a reunião do momento é o portal.
        $this->reuniao(now()->addWeek());           // futura, não conta
        $deHoje = $this->reuniao();
        [, $user] = $this->convocada($deHoje);

        $this->actingAs($user)->get(route('empresa.checkin'))
            ->assertOk()
            ->assertSee($deHoje->title)
            ->assertSee('Código mostrado na sala');
    }

    #[Test]
    public function o_qr_da_recepcao_registra_sem_digitar_nada(): void
    {
        // Esse QR já carrega o código do dia: escaneou, está registrado.
        $reuniao = $this->reuniao();
        [, $user, $presenca] = $this->convocada($reuniao);

        $this->actingAs($user)
            ->get(route('empresa.checkin', ['c' => $reuniao->codigoDeCheckin()]))
            ->assertRedirect(route('empresa.checkin'));

        $this->assertNotNull($presenca->fresh()->checked_in_at);
    }

    #[Test]
    public function qr_com_codigo_velho_nao_registra(): void
    {
        // O cartaz plastificado nunca carrega código justamente por isto: um
        // código fotografado valeria para sempre.
        $reuniao = $this->reuniao();
        [, $user, $presenca] = $this->convocada($reuniao);

        $this->actingAs($user)
            ->get(route('empresa.checkin', ['c' => 'ZZ999']))
            ->assertRedirect(route('empresa.checkin'));

        $this->assertNull($presenca->fresh()->checked_in_at);
    }

    #[Test]
    public function sem_reuniao_agora_o_qr_explica_em_vez_de_dar_erro(): void
    {
        $this->reuniao(now()->addWeek());
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $company->id]);

        $this->actingAs($user)->get(route('empresa.checkin'))
            ->assertOk()
            ->assertSee('Nenhuma reunião acontecendo agora');
    }

    #[Test]
    public function pela_tela_do_qr_o_codigo_da_sala_registra(): void
    {
        $reuniao = $this->reuniao();
        [, $user, $presenca] = $this->convocada($reuniao);

        $this->actingAs($user)->post(route('empresa.checkin.store'), [
            'meeting_id' => $reuniao->id,
            'codigo' => $reuniao->codigoDeCheckin(),
        ])->assertRedirect(route('empresa.checkin'));

        $this->assertNotNull($presenca->fresh()->checked_in_at);
    }

    #[Test]
    public function o_cartaz_aponta_para_o_endereco_fixo_e_nao_para_uma_reuniao(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)->get(route('admin.reunioes.cartaz'))
            ->assertOk()
            ->assertSee(route('empresa.checkin'), false)
            ->assertSee('Registre sua presença')
            // Espaço para a diretoria escrever o código do dia no plástico.
            ->assertSee('Código desta reunião');
    }

    // ----- Integração com o que já existia -----

    #[Test]
    public function o_check_in_alimenta_a_participacao_e_o_quorum(): void
    {
        // O check-in liga a MESMA coluna que a lista de presença e os
        // relatórios já usavam; nada precisa ser recontado em outro lugar.
        $reuniao = $this->reuniao();
        $reuniao->update(['quorum_minimum' => 1]);
        [, $user] = $this->convocada($reuniao);

        $this->actingAs($user)->post(route('empresa.reunioes.checkin', $reuniao), ['codigo' => $reuniao->codigoDeCheckin()]);

        $reuniao->refresh()->load('attendances');

        $this->assertSame(1, $reuniao->attendedCount());
        $this->assertSame(1, $reuniao->checkedInCount());
    }

    #[Test]
    public function a_tela_da_empresa_mostra_o_check_in_no_dia(): void
    {
        $reuniao = $this->reuniao();
        [, $user] = $this->convocada($reuniao);

        $this->actingAs($user)->get(route('empresa.reunioes.index'))
            ->assertOk()
            ->assertSee('Já está na reunião? Faça seu check-in.');
    }

    #[Test]
    public function a_tela_da_diretoria_mostra_o_codigo_da_sala(): void
    {
        $reuniao = $this->reuniao();
        $this->convocada($reuniao);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)->get(route('admin.reunioes.show', $reuniao))
            ->assertOk()
            ->assertSee('Código da sala')
            ->assertSee($reuniao->fresh()->codigoDeCheckin());
    }

    #[Test]
    public function fora_da_janela_a_recepcao_e_o_cartaz_continuam_a_vista(): void
    {
        // Com todas as reuniões no passado, esconder o cartão fazia o check-in
        // inteiro sumir do sistema: ninguém achava por onde começar.
        $reuniao = $this->reuniao(now()->subMonth());
        $this->convocada($reuniao);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)->get(route('admin.reunioes.show', $reuniao))
            ->assertOk()
            ->assertSee('Abrir recepção (tablet)')
            ->assertSee('Cartaz para imprimir')
            // Mas o código da sala, não: ele só vale dentro da janela.
            ->assertDontSee('Código da sala');
    }
}
