<?php

namespace Tests\Feature;

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Models\Company;
use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\MeetingMinute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MeetingTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Assembleia Geral Ordinária',
            'type' => 'assembleia_ordinaria',
            'status' => 'agendada',
            'scheduled_at' => now()->addWeek()->format('Y-m-d\TH:i'),
            'agenda' => [
                ['title' => 'Prestação de contas', 'presenter' => 'Tesoureiro'],
                ['title' => 'Eleição da diretoria'],
                ['title' => ''],
            ],
        ], $overrides);
    }

    #[Test]
    public function criar_reuniao_monta_a_pauta_e_convoca_as_empresas(): void
    {
        Company::factory()->count(3)->create();
        Company::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->post(route('admin.reunioes.store'), $this->payload())
            ->assertRedirect();

        $reuniao = Meeting::firstOrFail();

        // Linha vazia da pauta e descartada.
        $this->assertCount(2, $reuniao->agendaItems);
        $this->assertSame('Prestação de contas', $reuniao->agendaItems->first()->title);

        // So empresas publicadas entram na lista de convocacao.
        $this->assertCount(3, $reuniao->attendances);
        $this->assertTrue($reuniao->attendances->every(fn ($p) => $p->status === AttendanceStatus::Pendente));
    }

    #[Test]
    public function o_token_do_link_e_gerado_no_servidor(): void
    {
        $this->actingAs($this->admin())->post(route('admin.reunioes.store'), $this->payload([
            'public_token' => 'token-escolhido-pelo-atacante',
        ]));

        $reuniao = Meeting::firstOrFail();

        $this->assertNotSame('token-escolhido-pelo-atacante', $reuniao->public_token);
        $this->assertSame(48, strlen($reuniao->public_token));
    }

    #[Test]
    public function a_convocacao_abre_sem_login_e_mostra_a_pauta(): void
    {
        $reuniao = Meeting::factory()->create(['title' => 'Assembleia de Agosto']);
        $reuniao->agendaItems()->create(['title' => 'Prestação de contas', 'position' => 0]);
        Company::factory()->create(['name' => 'Alpha Digital']);

        $this->get(route('reuniao.convocacao', $reuniao->public_token))
            ->assertOk()
            ->assertSee('Assembleia de Agosto')
            ->assertSee('Prestação de contas')
            ->assertSee('Alpha Digital');
    }

    #[Test]
    public function token_invalido_da_404(): void
    {
        $this->get(route('reuniao.convocacao', 'token-que-nao-existe'))->assertNotFound();
    }

    #[Test]
    public function qualquer_pessoa_com_o_link_confirma_presenca(): void
    {
        $reuniao = Meeting::factory()->create();
        $empresa = Company::factory()->create();

        $this->post(route('reuniao.confirmar', $reuniao->public_token), [
            'company_id' => $empresa->id,
            'status' => 'confirmado',
            'responded_by' => 'Maria Lima',
        ])->assertRedirect()->assertSessionHas('confirmacao_registrada');

        $presenca = $reuniao->attendances()->where('company_id', $empresa->id)->firstOrFail();

        $this->assertSame(AttendanceStatus::Confirmado, $presenca->status);
        $this->assertSame('Maria Lima', $presenca->responded_by);
        $this->assertNotNull($presenca->responded_at);
    }

    #[Test]
    public function responder_de_novo_atualiza_em_vez_de_duplicar(): void
    {
        $reuniao = Meeting::factory()->create();
        $empresa = Company::factory()->create();

        foreach (['confirmado', 'recusado'] as $resposta) {
            $this->post(route('reuniao.confirmar', $reuniao->public_token), [
                'company_id' => $empresa->id,
                'status' => $resposta,
                'responded_by' => 'Maria Lima',
            ]);
        }

        $this->assertSame(1, $reuniao->attendances()->where('company_id', $empresa->id)->count());
        $this->assertSame(
            AttendanceStatus::Recusado,
            $reuniao->attendances()->where('company_id', $empresa->id)->first()->status
        );
    }

    #[Test]
    public function nao_confirma_depois_do_prazo(): void
    {
        $reuniao = Meeting::factory()->create([
            'confirmations_until' => now()->subDay(),
        ]);
        $empresa = Company::factory()->create();

        $this->post(route('reuniao.confirmar', $reuniao->public_token), [
            'company_id' => $empresa->id,
            'status' => 'confirmado',
            'responded_by' => 'Maria',
        ])->assertSessionHasErrors('company_id');

        $this->assertSame(0, $reuniao->attendances()->count());
    }

    #[Test]
    public function reuniao_cancelada_nao_aceita_confirmacao(): void
    {
        $reuniao = Meeting::factory()->cancelada()->create();
        $empresa = Company::factory()->create();

        $this->post(route('reuniao.confirmar', $reuniao->public_token), [
            'company_id' => $empresa->id,
            'status' => 'confirmado',
            'responded_by' => 'Maria',
        ])->assertSessionHasErrors('company_id');
    }

    #[Test]
    public function o_arquivo_de_calendario_e_valido(): void
    {
        $reuniao = Meeting::factory()->create(['title' => 'Assembleia de Agosto']);

        $resposta = $this->get(route('reuniao.calendario', $reuniao->public_token))->assertOk();
        $conteudo = $resposta->getContent();

        $resposta->assertHeader('content-type', 'text/calendar; charset=utf-8');
        $this->assertStringContainsString('BEGIN:VCALENDAR', $conteudo);
        $this->assertStringContainsString('SUMMARY:Assembleia de Agosto', $conteudo);
        $this->assertStringContainsString('END:VEVENT', $conteudo);
    }

    #[Test]
    public function a_empresa_confirma_pelo_painel_dela(): void
    {
        $empresa = Company::factory()->create();
        $user = User::factory()->forCompany($empresa)->create();
        $reuniao = Meeting::factory()->create();

        $this->actingAs($user)->get(route('empresa.reunioes.index'))->assertOk();

        $this->actingAs($user)
            ->post(route('empresa.reunioes.confirmar', $reuniao), ['status' => 'confirmado'])
            ->assertRedirect();

        $presenca = $reuniao->attendances()->where('company_id', $empresa->id)->firstOrFail();

        $this->assertSame(AttendanceStatus::Confirmado, $presenca->status);
        $this->assertSame($user->name, $presenca->responded_by);
    }

    #[Test]
    public function registrar_presenca_marca_a_reuniao_como_realizada(): void
    {
        $presente = Company::factory()->create();
        $ausente = Company::factory()->create();
        $reuniao = Meeting::factory()->passada()->create();

        foreach ([$presente, $ausente] as $empresa) {
            $reuniao->attendances()->create(['company_id' => $empresa->id, 'status' => AttendanceStatus::Pendente]);
        }

        $this->actingAs($this->admin())
            ->post(route('admin.reunioes.presencas', $reuniao), ['presentes' => [$presente->id]])
            ->assertRedirect();

        $reuniao->refresh();

        $this->assertSame(MeetingStatus::Realizada, $reuniao->status);
        $this->assertTrue($reuniao->attendances()->where('company_id', $presente->id)->first()->attended);
        $this->assertFalse($reuniao->attendances()->where('company_id', $ausente->id)->first()->attended);
    }

    #[Test]
    public function o_quorum_usa_confirmacoes_antes_e_presenca_depois(): void
    {
        $empresas = Company::factory()->count(3)->create();
        $reuniao = Meeting::factory()->create(['quorum_minimum' => 2]);

        foreach ($empresas as $indice => $empresa) {
            $reuniao->attendances()->create([
                'company_id' => $empresa->id,
                'status' => $indice < 2 ? AttendanceStatus::Confirmado : AttendanceStatus::Pendente,
                'attended' => $indice < 1,
            ]);
        }

        $reuniao->load('attendances');

        // Agendada: conta quem confirmou -> 2, atinge o minimo.
        $this->assertTrue($reuniao->hasQuorum());

        // Realizada: conta quem compareceu -> 1, nao atinge.
        $reuniao->status = MeetingStatus::Realizada;
        $this->assertFalse($reuniao->hasQuorum());
    }

    #[Test]
    public function editar_a_pauta_preserva_os_resultados_ja_registrados(): void
    {
        $reuniao = Meeting::factory()->create();
        $item = $reuniao->agendaItems()->create(['title' => 'Prestação de contas', 'position' => 0]);

        $this->actingAs($this->admin())
            ->post(route('admin.reunioes.resultado', [$reuniao, $item]), [
                'outcome' => 'aprovado',
                'votes_for' => 8,
                'votes_against' => 1,
                'votes_abstain' => 0,
            ])->assertRedirect();

        // Reenviar o formulario da reuniao com o mesmo item nao pode apagar o
        // resultado: por isso o sync mantem os ids.
        $this->actingAs($this->admin())->put(route('admin.reunioes.update', $reuniao), $this->payload([
            'agenda' => [
                ['id' => $item->id, 'title' => 'Prestação de contas', 'presenter' => 'Tesoureiro'],
            ],
        ]));

        $item->refresh();

        $this->assertSame('aprovado', $item->outcome);
        $this->assertSame(8, $item->votes_for);
        $this->assertSame('Tesoureiro', $item->presenter);
    }

    #[Test]
    public function gera_a_ata_a_partir_da_reuniao(): void
    {
        $empresa = Company::factory()->create(['name' => 'Alpha Digital']);
        $reuniao = Meeting::factory()->realizada()->create([
            'title' => 'Assembleia de Agosto',
            'quorum_minimum' => 1,
        ]);

        $item = $reuniao->agendaItems()->create([
            'title' => 'Prestação de contas',
            'position' => 0,
            'outcome' => 'aprovado',
            'votes_for' => 9,
            'votes_against' => 0,
            'votes_abstain' => 1,
        ]);

        $reuniao->attendances()->create([
            'company_id' => $empresa->id,
            'status' => AttendanceStatus::Confirmado,
            'attended' => true,
        ]);

        $reuniao->actionItems()->create([
            'title' => 'Enviar balanço às empresas',
            'responsible' => 'Secretaria',
            'due_date' => now()->addWeek(),
            'status' => 'pendente',
            'meeting_agenda_item_id' => $item->id,
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.reunioes.gerar-ata', $reuniao))
            ->assertRedirect();

        $ata = MeetingMinute::firstOrFail();

        $this->assertSame('Assembleia de Agosto', $ata->title);
        $this->assertFalse($ata->is_published, 'A ata nasce como rascunho para revisão.');
        $this->assertStringContainsString('Alpha Digital', $ata->body);
        $this->assertStringContainsString('Prestação de contas', $ata->body);
        $this->assertStringContainsString('9 a favor', $ata->body);
        $this->assertStringContainsString('Quórum mínimo', $ata->body);
        $this->assertStringContainsString('Enviar balanço às empresas', $ata->body);
        $this->assertSame($ata->id, $reuniao->fresh()->meeting_minute_id);
    }

    #[Test]
    public function nao_gera_a_ata_duas_vezes(): void
    {
        $reuniao = Meeting::factory()->realizada()->create();

        $this->actingAs($this->admin())->post(route('admin.reunioes.gerar-ata', $reuniao));
        $this->actingAs($this->admin())->post(route('admin.reunioes.gerar-ata', $reuniao))
            ->assertSessionHasErrors('ata');

        $this->assertSame(1, MeetingMinute::count());
    }

    #[Test]
    public function encaminhamento_vencido_e_identificado(): void
    {
        $reuniao = Meeting::factory()->realizada()->create();

        $vencido = $reuniao->actionItems()->create([
            'title' => 'Tarefa atrasada', 'due_date' => now()->subWeek(), 'status' => 'pendente',
        ]);
        $noPrazo = $reuniao->actionItems()->create([
            'title' => 'Tarefa no prazo', 'due_date' => now()->addWeek(), 'status' => 'pendente',
        ]);
        $concluido = $reuniao->actionItems()->create([
            'title' => 'Tarefa feita', 'due_date' => now()->subWeek(), 'status' => 'concluido',
        ]);

        $this->assertTrue($vencido->isOverdue());
        $this->assertFalse($noPrazo->isOverdue());
        $this->assertFalse($concluido->isOverdue(), 'Concluído não conta como vencido.');
        $this->assertSame(1, MeetingActionItem::overdue()->count());
    }

    #[Test]
    public function a_empresa_nao_entra_na_gestao_de_reunioes(): void
    {
        $user = User::factory()->forCompany(Company::factory()->create())->create();

        $this->actingAs($user)->get(route('admin.reunioes.index'))->assertRedirect(route('empresa.dashboard'));
    }

    #[Test]
    public function as_telas_do_admin_respondem(): void
    {
        $admin = $this->admin();
        Company::factory()->create();
        $reuniao = Meeting::factory()->create();
        $reuniao->agendaItems()->create(['title' => 'Item', 'position' => 0]);

        foreach ([
            route('admin.reunioes.index'),
            route('admin.reunioes.create'),
            route('admin.reunioes.show', $reuniao),
            route('admin.reunioes.edit', $reuniao),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk("Falhou em {$url}");
        }
    }
}
