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
}
