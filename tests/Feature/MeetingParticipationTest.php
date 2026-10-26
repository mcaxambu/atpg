<?php

namespace Tests\Feature;

use App\Enums\AdminModule;
use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\User;
use App\Support\MeetingParticipation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Participação dos associados nas reuniões.
 *
 * O cuidado central: RESPOSTA à convocação e PRESENÇA no dia são medidas
 * diferentes. Reunião que ainda não foi encerrada NÃO conta como falta de
 * ninguém — era o jeito mais fácil de esta tela mentir, já que hoje nenhuma
 * reunião do portal tem lista de presença registrada.
 */
class MeetingParticipationTest extends TestCase
{
    use RefreshDatabase;

    private function diretoria(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
    }

    private function reuniao(MeetingStatus $status, string $titulo = 'Assembleia'): Meeting
    {
        return Meeting::create([
            'title' => $titulo,
            'type' => MeetingType::AssembleiaOrdinaria,
            'status' => $status,
            'scheduled_at' => now()->subWeek(),
            'created_by' => null,
        ]);
    }

    private function convocar(Meeting $reuniao, Company $empresa, AttendanceStatus $resposta, ?bool $compareceu = null): MeetingAttendance
    {
        return MeetingAttendance::create([
            'meeting_id' => $reuniao->id,
            'company_id' => $empresa->id,
            'status' => $resposta,
            'attended' => $compareceu,
            'responded_at' => $resposta === AttendanceStatus::Pendente ? null : now()->subDays(3),
        ]);
    }

    // ----- Quem alcança -----

    #[Test]
    public function admin_sem_o_modulo_de_reunioes_nao_alcanca(): void
    {
        $semModulo = User::factory()->create([
            'role' => UserRole::Admin,
            'abilities' => [AdminModule::Members->value],
        ]);

        $this->actingAs($semModulo)->get(route('admin.reunioes.participacao'))->assertForbidden();
    }

    #[Test]
    public function empresa_nao_alcanca_o_relatorio(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $company->id]);

        $this->actingAs($user)->get(route('admin.reunioes.participacao'))->assertRedirect();
    }

    // ----- Reunião não encerrada não vira falta -----

    #[Test]
    public function reuniao_agendada_nao_conta_como_falta(): void
    {
        $empresa = Company::factory()->create();
        $agendada = $this->reuniao(MeetingStatus::Agendada);
        $this->convocar($agendada, $empresa, AttendanceStatus::Confirmado);

        $resumo = MeetingParticipation::resumo();

        // Sem reunião encerrada não há presença a medir: nulo, e não zero.
        $this->assertNull($resumo['media_presenca']);
        $this->assertSame(0, $resumo['reunioes_realizadas']);

        $linha = MeetingParticipation::porEmpresa()->firstWhere('empresa.id', $empresa->id);
        $this->assertNull($linha['taxa_presenca']);
    }

    #[Test]
    public function a_tela_avisa_quando_nao_ha_presenca_para_medir(): void
    {
        $empresa = Company::factory()->create();
        $this->convocar($this->reuniao(MeetingStatus::Agendada), $empresa, AttendanceStatus::Pendente);

        $this->actingAs($this->diretoria())
            ->get(route('admin.reunioes.participacao'))
            ->assertOk()
            ->assertSee('Ainda não há presença para medir.')
            ->assertSee('sem dados');
    }

    // ----- As contas -----

    #[Test]
    public function a_media_de_presenca_usa_so_as_reunioes_encerradas(): void
    {
        $presente = Company::factory()->create(['name' => 'Sempre Presente']);
        $faltante = Company::factory()->create(['name' => 'Nunca Vem']);

        $realizada = $this->reuniao(MeetingStatus::Realizada, 'Assembleia de agosto');
        $this->convocar($realizada, $presente, AttendanceStatus::Confirmado, compareceu: true);
        $this->convocar($realizada, $faltante, AttendanceStatus::Confirmado, compareceu: false);

        // A futura entra na conta de RESPOSTA, mas não na de presença.
        $futura = $this->reuniao(MeetingStatus::Agendada, 'Próxima assembleia');
        $this->convocar($futura, $presente, AttendanceStatus::Confirmado);
        $this->convocar($futura, $faltante, AttendanceStatus::Pendente);

        $resumo = MeetingParticipation::resumo();

        $this->assertSame(1, $resumo['reunioes_realizadas']);
        $this->assertSame(50, $resumo['media_presenca']);   // 1 de 2 convocados
        $this->assertSame(75, $resumo['taxa_resposta']);    // 3 de 4 convocações

        $linhas = MeetingParticipation::porEmpresa();

        $this->assertSame(100, $linhas->firstWhere('empresa.id', $presente->id)['taxa_presenca']);
        $this->assertSame(0, $linhas->firstWhere('empresa.id', $faltante->id)['taxa_presenca']);
    }

    #[Test]
    public function a_lista_mostra_as_duas_medidas_por_empresa(): void
    {
        $empresa = Company::factory()->create(['name' => 'Empresa Exemplo']);
        $realizada = $this->reuniao(MeetingStatus::Realizada);
        $this->convocar($realizada, $empresa, AttendanceStatus::Confirmado, compareceu: true);

        $this->actingAs($this->diretoria())
            ->get(route('admin.reunioes.participacao'))
            ->assertOk()
            ->assertSee('Empresa Exemplo')
            ->assertSee('100%');
    }

    // ----- A ficha da empresa -----

    #[Test]
    public function clicar_na_empresa_abre_reuniao_a_reuniao(): void
    {
        $empresa = Company::factory()->create(['name' => 'Empresa Exemplo']);

        $veio = $this->reuniao(MeetingStatus::Realizada, 'Assembleia que ela veio');
        $this->convocar($veio, $empresa, AttendanceStatus::Confirmado, compareceu: true);

        $faltou = $this->reuniao(MeetingStatus::Realizada, 'Assembleia que ela faltou');
        $this->convocar($faltou, $empresa, AttendanceStatus::Recusado, compareceu: false);

        $resposta = $this->actingAs($this->diretoria())
            ->get(route('admin.reunioes.participacao.empresa', $empresa))
            ->assertOk();

        $resposta->assertSee('Assembleia que ela veio');
        $resposta->assertSee('Assembleia que ela faltou');
        $resposta->assertSee('compareceu');
        $resposta->assertSee('faltou');
        $resposta->assertSee('1 de 2 reuniões');
    }

    #[Test]
    public function a_ficha_nao_chama_de_falta_a_reuniao_que_ainda_vai_acontecer(): void
    {
        $empresa = Company::factory()->create();
        $this->convocar($this->reuniao(MeetingStatus::Agendada, 'Reunião de novembro'), $empresa, AttendanceStatus::Pendente);

        $this->actingAs($this->diretoria())
            ->get(route('admin.reunioes.participacao.empresa', $empresa))
            ->assertOk()
            ->assertSee('ainda não encerrada')
            ->assertDontSee('faltou');
    }

    #[Test]
    public function empresa_nunca_convocada_nao_aparece_na_lista(): void
    {
        // Entrar com 0% numa lista de participação seria acusação falsa.
        Company::factory()->create(['name' => 'Entrou Esta Semana']);

        $this->actingAs($this->diretoria())
            ->get(route('admin.reunioes.participacao'))
            ->assertOk()
            ->assertDontSee('Entrou Esta Semana');
    }
}
