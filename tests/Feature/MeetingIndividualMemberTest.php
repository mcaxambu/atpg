<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Associado pessoa fisica na reuniao.
 *
 * A associacao tem gente sem empresa, e numa reuniao esse associado vale o
 * mesmo que uma empresa: e convocado, confirma, faz check-in e entra no
 * quorum (decisao do usuario em 06/11/2026).
 *
 * O teste negativo mais importante daqui: membro QUE TEM empresa nao ganha
 * linha propria — ele ja esta representado pela empresa dele, e duas linhas
 * contariam a mesma cadeira duas vezes.
 */
class MeetingIndividualMemberTest extends TestCase
{
    use RefreshDatabase;

    private function diretoria(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
    }

    private function membroSemEmpresa(string $nome = 'Lorenzo Pessoa Física'): Member
    {
        return Member::factory()->create(['company_id' => null, 'name' => $nome]);
    }

    /**
     * Cria a reunião PELA TELA, que é onde a lista de convocação é montada.
     */
    private function criarReuniao(array $dados = []): Meeting
    {
        $this->actingAs($this->diretoria())
            ->post(route('admin.reunioes.store'), $dados + [
                'title' => 'Assembleia de novembro',
                'type' => MeetingType::AssembleiaOrdinaria->value,
                'status' => MeetingStatus::Agendada->value,
                'scheduled_at' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        return Meeting::latest('id')->firstOrFail();
    }

    // ----- Convocação -----

    #[Test]
    public function a_convocacao_inclui_o_associado_sem_empresa(): void
    {
        $empresa = Company::factory()->create();
        $sozinho = $this->membroSemEmpresa();

        $reuniao = $this->criarReuniao();

        $this->assertSame(2, $reuniao->attendances()->count());
        $this->assertTrue($reuniao->attendances()->where('company_id', $empresa->id)->exists());
        $this->assertTrue($reuniao->attendances()->where('member_id', $sozinho->id)->exists());
    }

    #[Test]
    public function membro_de_empresa_associada_nao_ganha_linha_propria(): void
    {
        // Ele participa PELA empresa; duas linhas contariam a mesma cadeira
        // duas vezes no quórum.
        $empresa = Company::factory()->create();
        Member::factory()->create(['company_id' => $empresa->id]);

        $reuniao = $this->criarReuniao();

        $this->assertSame(1, $reuniao->attendances()->count());
        $this->assertNull($reuniao->attendances()->first()->member_id);
    }

    // ----- Confirmação -----

    #[Test]
    public function o_associado_sem_empresa_confirma_pelo_link_publico(): void
    {
        $sozinho = $this->membroSemEmpresa();
        $reuniao = Meeting::factory()->create();

        $this->post(route('reuniao.confirmar', $reuniao->public_token), [
            'participante' => 'membro:'.$sozinho->id,
            'status' => 'confirmado',
            'responded_by' => 'Lorenzo',
        ])->assertRedirect()->assertSessionHas('confirmacao_registrada');

        $presenca = $reuniao->attendances()->where('member_id', $sozinho->id)->firstOrFail();

        $this->assertSame(AttendanceStatus::Confirmado, $presenca->status);
        $this->assertNull($presenca->company_id);
    }

    #[Test]
    public function a_chave_separa_empresa_de_membro_com_o_mesmo_id(): void
    {
        // Sem o tipo na chave, "3" cairia na linha de outro associado.
        $empresa = Company::factory()->create();
        $sozinho = $this->membroSemEmpresa();
        $reuniao = Meeting::factory()->create();

        $this->post(route('reuniao.confirmar', $reuniao->public_token), [
            'participante' => 'membro:'.$sozinho->id,
            'status' => 'confirmado',
            'responded_by' => 'Lorenzo',
        ]);

        $this->assertFalse($reuniao->attendances()->where('company_id', $empresa->id)->exists());
    }

    #[Test]
    public function chave_inventada_nao_cria_linha(): void
    {
        $reuniao = Meeting::factory()->create();

        $this->post(route('reuniao.confirmar', $reuniao->public_token), [
            'participante' => 'membro:9999',
            'status' => 'confirmado',
            'responded_by' => 'Alguém',
        ])->assertSessionHasErrors('participante');

        $this->assertSame(0, $reuniao->attendances()->count());
    }

    // ----- Check-in e quórum -----

    #[Test]
    public function o_associado_sem_empresa_faz_check_in_e_entra_no_quorum(): void
    {
        $sozinho = $this->membroSemEmpresa();
        $user = User::factory()->create(['role' => UserRole::Member, 'member_id' => $sozinho->id]);

        $reuniao = Meeting::create([
            'title' => 'Assembleia de novembro',
            'type' => MeetingType::AssembleiaOrdinaria,
            'status' => MeetingStatus::Agendada,
            'scheduled_at' => now(),
            'quorum_minimum' => 1,
            'created_by' => null,
        ]);

        $presenca = $reuniao->attendances()->create([
            'member_id' => $sozinho->id,
            'status' => AttendanceStatus::Confirmado,
        ]);

        $this->actingAs($user)
            ->post(route('painel.reunioes.checkin', $reuniao), ['codigo' => $reuniao->codigoDeCheckin()])
            ->assertRedirect();

        $presenca->refresh();

        $this->assertTrue((bool) $presenca->attended);
        $this->assertSame(MeetingAttendance::CHECKIN_EMPRESA, $presenca->checked_in_source);
        $this->assertSame(1, $reuniao->fresh()->checkedInCount());
        $this->assertTrue($reuniao->fresh()->hasQuorum());
    }

    #[Test]
    public function o_painel_do_membro_abre_as_reunioes_dele(): void
    {
        $sozinho = $this->membroSemEmpresa();
        $user = User::factory()->create(['role' => UserRole::Member, 'member_id' => $sozinho->id]);

        Meeting::factory()->create(['title' => 'Assembleia de novembro']);

        $this->actingAs($user)->get(route('painel.reunioes.index'))
            ->assertOk()
            ->assertSee('Assembleia de novembro');
    }

    // ----- Nas telas da diretoria -----

    #[Test]
    public function a_diretoria_ve_o_nome_do_associado_na_lista_de_presenca(): void
    {
        $sozinho = $this->membroSemEmpresa('Lorenzo Pessoa Física');
        $reuniao = Meeting::factory()->create();
        $reuniao->attendances()->create([
            'member_id' => $sozinho->id,
            'status' => AttendanceStatus::Pendente,
        ]);

        $this->actingAs($this->diretoria())->get(route('admin.reunioes.show', $reuniao))
            ->assertOk()
            ->assertSee('Lorenzo Pessoa Física')
            ->assertSee('associado pessoa física');
    }

    #[Test]
    public function a_participacao_conta_o_associado_sem_empresa(): void
    {
        $sozinho = $this->membroSemEmpresa('Lorenzo Pessoa Física');
        $reuniao = Meeting::factory()->create(['status' => MeetingStatus::Realizada]);
        $reuniao->attendances()->create([
            'member_id' => $sozinho->id,
            'status' => AttendanceStatus::Confirmado,
            'attended' => true,
        ]);

        $this->actingAs($this->diretoria())->get(route('admin.reunioes.participacao'))
            ->assertOk()
            ->assertSee('Lorenzo Pessoa Física');

        $this->actingAs($this->diretoria())
            ->get(route('admin.reunioes.participacao.associado', ['membro', $sozinho->id]))
            ->assertOk()
            ->assertSee('Lorenzo Pessoa Física');
    }

    #[Test]
    public function a_ata_lista_o_associado_sem_empresa_entre_os_presentes(): void
    {
        $sozinho = $this->membroSemEmpresa('Lorenzo Pessoa Física');
        $reuniao = Meeting::factory()->create(['status' => MeetingStatus::Realizada]);
        $reuniao->attendances()->create([
            'member_id' => $sozinho->id,
            'status' => AttendanceStatus::Confirmado,
            'attended' => true,
        ]);

        $this->actingAs($this->diretoria())
            ->post(route('admin.reunioes.gerar-ata', $reuniao))
            ->assertRedirect();

        $this->assertStringContainsString('Lorenzo Pessoa Física', $reuniao->fresh()->minute->body);
    }
}
