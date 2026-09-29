<?php

namespace Tests\Feature;

use App\Actions\ModerateRegistration;
use App\Enums\ProspectStage;
use App\Enums\UserRole;
use App\Mail\OnboardingReminderMail;
use App\Mail\ProspectInviteMail;
use App\Mail\WeeklyFunnelDigestMail;
use App\Models\Company;
use App\Models\Member;
use App\Models\Prospect;
use App\Models\User;
use App\Support\OnboardingProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Da conversa ao associado dentro: convite, cadastro, aprovação e o que falta.
 *
 * O ponto desta fase é NÃO depender de alguém lembrar de atualizar nada: o
 * cadastro recebido encontra sozinho o prospecto que o convidou, a aprovação
 * fecha o funil sozinha, e o que falta no perfil é lido do banco.
 */
class ProspectOnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function diretoria(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
    }

    // ----- Convite -----

    #[Test]
    public function enviar_convite_move_a_etapa_e_registra_o_envio(): void
    {
        Mail::fake();

        $prospecto = Prospect::factory()->create(['email' => 'contato@empresa.test', 'stage' => ProspectStage::Reuniao]);

        $this->actingAs($this->diretoria())
            ->post(route('admin.crm.invite', $prospecto), ['mensagem' => 'Foi ótimo conversar.'])
            ->assertRedirect();

        Mail::assertSent(ProspectInviteMail::class, fn ($mail) => $mail->hasTo('contato@empresa.test'));

        $prospecto->refresh();

        $this->assertSame(ProspectStage::Convite, $prospecto->stage);
        $this->assertNotNull($prospecto->invited_at);
        $this->assertNotNull($prospecto->invite_token);
        $this->assertTrue($prospecto->interactions->contains(fn ($i) => str_contains($i->summary, 'Convite de cadastro enviado')));
    }

    #[Test]
    public function sem_email_o_convite_nao_sai_mas_o_link_continua_valendo(): void
    {
        Mail::fake();

        $prospecto = Prospect::factory()->create(['email' => null]);

        $this->actingAs($this->diretoria())
            ->post(route('admin.crm.invite', $prospecto))
            ->assertSessionHasErrors('email');

        Mail::assertNothingSent();

        // O link existe de qualquer jeito: dá para mandar por WhatsApp.
        $this->assertStringContainsString('convite=', $prospecto->linkDeCadastro());
    }

    // ----- O cadastro encontra o prospecto -----

    #[Test]
    public function empresa_que_se_cadastra_pelo_convite_cai_na_ficha_certa(): void
    {
        Mail::fake();
        Storage::fake('public');

        $prospecto = Prospect::factory()->create(['stage' => ProspectStage::Convite]);
        $prospecto->linkDeCadastro();

        $this->post(route('companies.register.store'), $this->dadosDeEmpresa([
            'convite' => $prospecto->fresh()->invite_token,
        ]))->assertRedirect();

        $empresa = Company::firstOrFail();
        $prospecto->refresh();

        $this->assertSame($empresa->id, $prospecto->company_id);
        $this->assertSame(ProspectStage::Cadastro, $prospecto->stage);
        $this->assertTrue($prospecto->interactions->contains(fn ($i) => str_contains($i->summary, 'Cadastro recebido pelo convite')));
    }

    #[Test]
    public function cadastro_sem_convite_segue_normal(): void
    {
        Mail::fake();
        Storage::fake('public');

        $this->post(route('companies.register.store'), $this->dadosDeEmpresa())->assertRedirect();

        $this->assertSame(1, Company::count());
        $this->assertSame(0, Prospect::whereNotNull('company_id')->count());
    }

    #[Test]
    public function convite_invalido_nao_derruba_o_cadastro(): void
    {
        // Quem está se associando não tem nada a ver com o funil interno:
        // token errado não pode virar erro na cara da pessoa.
        Mail::fake();
        Storage::fake('public');

        $this->post(route('companies.register.store'), $this->dadosDeEmpresa(['convite' => 'nao-existe']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Company::count());
    }

    #[Test]
    public function o_mesmo_convite_nao_captura_um_segundo_cadastro(): void
    {
        Mail::fake();
        Storage::fake('public');

        $prospecto = Prospect::factory()->create();
        $prospecto->linkDeCadastro();
        $token = $prospecto->fresh()->invite_token;

        $this->post(route('companies.register.store'), $this->dadosDeEmpresa(['convite' => $token]));
        $primeira = Company::firstOrFail();

        $this->post(route('companies.register.store'), $this->dadosDeEmpresa([
            'convite' => $token,
            'name' => 'Outra Empresa',
            'cnpj' => '11.222.333/0001-81',
            'email' => 'outra@empresa.test',
        ]));

        $this->assertSame($primeira->id, $prospecto->fresh()->company_id);
    }

    // ----- Aprovação fecha o funil -----

    #[Test]
    public function aprovar_o_cadastro_move_o_prospecto_para_associado(): void
    {
        Mail::fake();

        $empresa = Company::factory()->pending()->create();
        $prospecto = Prospect::factory()->create(['company_id' => $empresa->id, 'stage' => ProspectStage::Cadastro]);

        app(ModerateRegistration::class)->approve($empresa, $this->diretoria());

        $this->assertSame(ProspectStage::Associado, $prospecto->fresh()->stage);
    }

    #[Test]
    public function aprovacao_sem_prospecto_ligado_nao_quebra(): void
    {
        Mail::fake();

        $empresa = Company::factory()->pending()->create();

        app(ModerateRegistration::class)->approve($empresa, $this->diretoria());

        $this->assertTrue($empresa->fresh()->isApproved());
    }

    // ----- O que falta no perfil -----

    #[Test]
    public function o_acompanhamento_le_os_passos_do_banco(): void
    {
        $empresa = Company::factory()->create([
            'description' => null,
            'logo_path' => null,
        ]);

        $progresso = new OnboardingProgress($empresa);
        $this->assertFalse($progresso->completo());

        $pendentes = array_column($progresso->pendentes(), 'chave');
        $this->assertContains('descricao', $pendentes);
        $this->assertContains('logo', $pendentes);
        $this->assertContains('acesso', $pendentes);

        // Agora com acesso criado e usado: dois passos a menos, sem ninguém
        // marcar nada.
        User::factory()->create([
            'role' => UserRole::Company,
            'company_id' => $empresa->id,
            'last_login_at' => now(),
        ]);

        $depois = new OnboardingProgress($empresa->fresh());
        $novosPendentes = array_column($depois->pendentes(), 'chave');

        $this->assertNotContains('acesso', $novosPendentes);
        $this->assertNotContains('entrou', $novosPendentes);
        $this->assertGreaterThan($progresso->concluidos(), $depois->concluidos());
    }

    #[Test]
    public function acesso_anterior_ao_registro_de_entradas_nao_conta_como_pendencia(): void
    {
        // O portal só passou a gravar a data de acesso em 29/09/2026. Para
        // quem já tinha acesso antes disso a resposta honesta é "não sei" — e
        // não pode virar cobrança.
        $empresa = Company::factory()->create();

        User::factory()->create([
            'role' => UserRole::Company,
            'company_id' => $empresa->id,
            'last_login_at' => null,
            'created_at' => '2026-08-01',
        ]);

        $progresso = new OnboardingProgress($empresa);
        $entrou = collect($progresso->passos)->firstWhere('chave', 'entrou');

        $this->assertTrue($entrou['indefinido']);
        $this->assertNotContains('entrou', array_column($progresso->pendentes(), 'chave'));
    }

    #[Test]
    public function acesso_criado_agora_e_nunca_usado_conta_como_pendencia(): void
    {
        $empresa = Company::factory()->create();

        User::factory()->create([
            'role' => UserRole::Company,
            'company_id' => $empresa->id,
            'last_login_at' => null,
            'created_at' => now(),
        ]);

        $pendentes = array_column((new OnboardingProgress($empresa))->pendentes(), 'chave');

        $this->assertContains('entrou', $pendentes);
    }

    #[Test]
    public function a_tela_de_acompanhamento_mostra_quem_esta_incompleto(): void
    {
        Company::factory()->create(['name' => 'Empresa Incompleta', 'description' => null, 'logo_path' => null]);

        $this->actingAs($this->diretoria())
            ->get(route('admin.crm.onboarding'))
            ->assertOk()
            ->assertSee('Empresa Incompleta');
    }

    #[Test]
    public function o_lembrete_lista_o_que_falta(): void
    {
        Mail::fake();

        $empresa = Company::factory()->create(['email' => 'contato@empresa.test', 'description' => null]);

        $this->actingAs($this->diretoria())->post(route('admin.crm.onboarding.remind'), [
            'tipo' => 'company',
            'id' => $empresa->id,
        ])->assertRedirect();

        Mail::assertSent(OnboardingReminderMail::class, function ($mail) use ($empresa) {
            return $mail->hasTo('contato@empresa.test')
                && $mail->associado->is($empresa)
                && $mail->progresso->pendentes() !== [];
        });
    }

    #[Test]
    public function o_login_marca_a_data_do_primeiro_acesso(): void
    {
        $user = User::factory()->create(['last_login_at' => null]);

        $this->actingAs($user); // não dispara o evento de login
        $this->assertNull($user->fresh()->last_login_at);

        event(new \Illuminate\Auth\Events\Login('web', $user, false));

        $this->assertNotNull($user->fresh()->last_login_at);
    }

    // ----- Resumo semanal -----

    #[Test]
    public function o_resumo_nao_sai_quando_nao_ha_nada_pendente(): void
    {
        Mail::fake();
        $this->diretoria();

        $this->artisan('atpg:resumo-associados')->assertSuccessful();

        Mail::assertNothingSent();
    }

    #[Test]
    public function o_resumo_vai_para_a_diretoria_com_as_pendencias(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null, 'email' => 'diretoria@atpg.test']);
        Prospect::factory()->atrasado()->create(['name' => 'Empresa Esquecida']);

        $this->artisan('atpg:resumo-associados')->assertSuccessful();

        Mail::assertSent(WeeklyFunnelDigestMail::class, function ($mail) use ($admin) {
            return $mail->hasTo($admin->email) && $mail->atrasados->count() === 1;
        });
    }

    #[Test]
    public function quem_nao_tem_o_modulo_nao_recebe_o_resumo(): void
    {
        Mail::fake();

        User::factory()->create(['role' => UserRole::Admin, 'abilities' => ['members'], 'email' => 'outro@atpg.test']);
        Prospect::factory()->atrasado()->create();

        $this->artisan('atpg:resumo-associados')->assertSuccessful();

        Mail::assertNotSent(WeeklyFunnelDigestMail::class, fn ($mail) => $mail->hasTo('outro@atpg.test'));
    }

    /** @return array<string, mixed> */
    private function dadosDeEmpresa(array $extra = []): array
    {
        return array_merge([
            'name' => 'Empresa Convidada',
            'legal_name' => 'Empresa Convidada LTDA',
            'cnpj' => '11.222.333/0001-81',
            'segment' => 'Software',
            'city' => 'Ponta Grossa',
            'state' => 'PR',
            'zip_code' => '84000-000',
            'address' => 'Rua Teste',
            'address_number' => '100',
            'neighborhood' => 'Centro',
            'description' => 'Uma empresa de teste do cadastro público.',
            'contact_name' => 'Fulano',
            'contact_role' => 'Diretor',
            'email' => 'convidada@empresa.test',
            'whatsapp' => '(42) 99999-9999',
            'logo' => \Illuminate\Http\UploadedFile::fake()->image('logo.png', 400, 400),
            // O formulário público exige o aceite da política de privacidade.
            'privacy_consent' => '1',
        ], $extra);
    }
}
