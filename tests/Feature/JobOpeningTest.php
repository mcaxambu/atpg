<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Enums\UserRole;
use App\Mail\JobApplicationReceivedMail;
use App\Mail\JobStatusMail;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Vagas publicadas pelas empresas e candidaturas recebidas pelo portal.
 */
class JobOpeningTest extends TestCase
{
    use RefreshDatabase;

    private function empresa(): array
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Company,
            'company_id' => $company->id,
        ]);

        return [$company, $user];
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Pessoa Desenvolvedora Back-end',
            'type' => 'clt',
            'workplace' => 'hibrido',
            'description' => 'Vaga para atuar no time de plataforma, com foco em APIs e integrações.',
            'city' => 'Ponta Grossa',
            'state' => 'PR',
        ], $overrides);
    }

    // ----- Painel da empresa -----

    #[Test]
    public function empresa_cadastra_vaga_e_ela_entra_como_pendente(): void
    {
        [$company, $user] = $this->empresa();

        $this->actingAs($user)->post('/empresa/vagas', $this->payload())
            ->assertRedirect('/empresa/vagas');

        $job = JobOpening::first();

        $this->assertSame($company->id, $job->company_id);
        $this->assertTrue($job->isPending());
        $this->assertFalse((bool) $job->is_active);
    }

    #[Test]
    public function editar_a_vaga_devolve_para_analise(): void
    {
        [$company, $user] = $this->empresa();
        $job = JobOpening::factory()->for($company)->create();

        $this->assertTrue($job->isApproved());

        $this->actingAs($user)->put("/empresa/vagas/{$job->id}", $this->payload(['title' => 'Novo título']))
            ->assertRedirect('/empresa/vagas');

        $job->refresh();
        $this->assertSame('Novo título', $job->title);
        $this->assertTrue($job->isPending());
        $this->assertFalse((bool) $job->is_active);
    }

    #[Test]
    public function empresa_nao_alcanca_vaga_de_outra_empresa(): void
    {
        [, $user] = $this->empresa();
        $deOutra = JobOpening::factory()->create();

        $this->actingAs($user)->get("/empresa/vagas/{$deOutra->id}/editar")->assertNotFound();
        $this->actingAs($user)->put("/empresa/vagas/{$deOutra->id}", $this->payload())->assertNotFound();
        $this->actingAs($user)->delete("/empresa/vagas/{$deOutra->id}")->assertNotFound();
        $this->actingAs($user)->get("/empresa/vagas/{$deOutra->id}/candidaturas")->assertNotFound();
    }

    #[Test]
    public function encerrar_tira_do_portal_sem_perder_as_candidaturas(): void
    {
        [$company, $user] = $this->empresa();
        $job = JobOpening::factory()->for($company)->create();
        JobApplication::factory()->for($job)->create();

        $this->actingAs($user)->patch("/empresa/vagas/{$job->id}/encerrar")->assertRedirect();

        $job->refresh();
        $this->assertFalse((bool) $job->is_active);
        $this->assertTrue($job->isApproved());
        $this->assertSame(1, $job->applications()->count());
    }

    #[Test]
    public function teto_salarial_nao_pode_ser_menor_que_o_piso(): void
    {
        [, $user] = $this->empresa();

        $this->actingAs($user)
            ->post('/empresa/vagas', $this->payload([
                'show_salary' => '1',
                'salary_min' => 8000,
                'salary_max' => 3000,
            ]))
            ->assertSessionHasErrors('salary_max');
    }

    #[Test]
    public function data_de_encerramento_no_passado_e_recusada(): void
    {
        [, $user] = $this->empresa();

        $this->actingAs($user)
            ->post('/empresa/vagas', $this->payload(['closes_at' => now()->subDay()->toDateString()]))
            ->assertSessionHasErrors('closes_at');
    }

    #[Test]
    public function as_telas_da_empresa_renderizam(): void
    {
        [$company, $user] = $this->empresa();
        $job = JobOpening::factory()->for($company)->rejected()->create();
        $application = JobApplication::factory()->for($job)->create();

        $this->actingAs($user)->get('/empresa/vagas')->assertOk()->assertSee($job->title);
        $this->actingAs($user)->get('/empresa/vagas/nova')->assertOk();
        $this->actingAs($user)->get("/empresa/vagas/{$job->id}/editar")->assertOk()
            ->assertSee($job->rejection_reason);
        $this->actingAs($user)->get("/empresa/vagas/{$job->id}/candidaturas")->assertOk()
            ->assertSee($application->name);
        $this->actingAs($user)->get("/empresa/vagas/{$job->id}/candidaturas/{$application->id}")->assertOk();
    }

    #[Test]
    public function abrir_a_candidatura_marca_como_vista(): void
    {
        [$company, $user] = $this->empresa();
        $job = JobOpening::factory()->for($company)->create();
        $application = JobApplication::factory()->for($job)->create();

        $this->assertNull($application->viewed_at);

        $this->actingAs($user)->get("/empresa/vagas/{$job->id}/candidaturas/{$application->id}")->assertOk();

        $this->assertNotNull($application->fresh()->viewed_at);
    }

    // ----- Moderação da associação -----

    #[Test]
    public function as_telas_do_admin_renderizam(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $job = JobOpening::factory()->pending()->create();

        $this->actingAs($admin)->get('/admin/vagas')->assertOk()->assertSee($job->title);
        $this->actingAs($admin)->get('/admin/vagas-pendentes')->assertOk()->assertSee($job->title);
        $this->actingAs($admin)->get("/admin/vagas/{$job->id}")->assertOk()->assertSee($job->title);
    }

    #[Test]
    public function diretoria_aprova_e_a_vaga_aparece_no_portal(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $job = JobOpening::factory()->pending()->create();

        $this->actingAs($admin)->patch("/admin/vagas/{$job->id}/aprovar")->assertRedirect();

        $job->refresh();
        $this->assertTrue($job->isApproved());
        $this->assertTrue($job->isPubliclyVisible());

        $this->get('/vagas')->assertOk()->assertSee($job->title);
    }

    #[Test]
    public function vaga_rejeitada_guarda_o_motivo_para_a_empresa_ver(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $job = JobOpening::factory()->pending()->create();

        $this->actingAs($admin)
            ->patch("/admin/vagas/{$job->id}/rejeitar", ['rejection_reason' => 'Faltou descrever o cargo.'])
            ->assertRedirect();

        $job->refresh();
        $this->assertTrue($job->isRejected());
        $this->assertSame('Faltou descrever o cargo.', $job->rejection_reason);
    }

    #[Test]
    public function admin_sem_o_modulo_vagas_leva_403(): void
    {
        $restrito = User::factory()->create([
            'role' => UserRole::Admin,
            'abilities' => ['members'],
        ]);

        $this->actingAs($restrito)->get('/admin/vagas')->assertForbidden();
        $this->actingAs($restrito)->get('/admin/vagas-pendentes')->assertForbidden();
    }

    // ----- Portal público -----

    #[Test]
    public function o_portal_mostra_so_vaga_aprovada_publicada_e_no_prazo(): void
    {
        $noAr = JobOpening::factory()->create(['title' => 'Vaga no ar']);
        $pendente = JobOpening::factory()->pending()->create(['title' => 'Vaga pendente']);
        $vencida = JobOpening::factory()->expired()->create(['title' => 'Vaga vencida']);

        $resposta = $this->get('/vagas')->assertOk();

        $resposta->assertSee($noAr->title);
        $resposta->assertDontSee($pendente->title);
        $resposta->assertDontSee($vencida->title);

        $this->get("/vagas/{$pendente->slug}")->assertNotFound();
        $this->get("/vagas/{$vencida->slug}")->assertNotFound();
        $this->get("/vagas/{$noAr->slug}")->assertOk();
    }

    #[Test]
    public function vaga_de_empresa_despublicada_nao_aparece(): void
    {
        $company = Company::factory()->create(['is_active' => false]);

        // Titulo fixo e distintivo de proposito: o faker ja gerou "qui", que
        // casa dentro de "por aqui" no texto da pagina vazia e fazia o teste
        // falhar sem que nada tivesse vazado.
        $job = JobOpening::factory()->for($company)->create([
            'title' => 'Vaga de empresa despublicada',
        ]);

        $this->get('/vagas')->assertOk()->assertDontSee($job->title);
        $this->get("/vagas/{$job->slug}")->assertNotFound();

        // A prova real: a consulta publica nao devolve a vaga.
        $this->assertSame(0, JobOpening::publiclyVisible()->count());
    }

    // ----- Candidatura -----

    #[Test]
    public function candidato_se_inscreve_e_o_curriculo_vai_para_o_disco_privado(): void
    {
        Storage::fake('local');
        $job = JobOpening::factory()->create();

        $this->post("/vagas/{$job->slug}/candidatura", [
            'name' => 'Ana Souza',
            'email' => 'ana@exemplo.com',
            'phone' => '42999990000',
            'message' => 'Tenho interesse na vaga.',
            'consent' => '1',
            'resume' => UploadedFile::fake()->create('curriculo.pdf', 200, 'application/pdf'),
        ])->assertRedirect("/vagas/{$job->slug}");

        $application = JobApplication::first();

        $this->assertSame('Ana Souza', $application->name);
        $this->assertNotNull($application->consented_at);
        Storage::disk('local')->assertExists($application->resume_path);

        // O curriculo NAO pode estar no disco publico.
        $this->assertStringStartsWith('job-applications/', $application->resume_path);
    }

    #[Test]
    public function candidatura_exige_consentimento_e_curriculo(): void
    {
        $job = JobOpening::factory()->create();

        $this->post("/vagas/{$job->slug}/candidatura", [
            'name' => 'Ana Souza',
            'email' => 'ana@exemplo.com',
        ])->assertSessionHasErrors(['consent', 'resume']);
    }

    #[Test]
    public function a_mesma_pessoa_nao_se_candidata_duas_vezes(): void
    {
        Storage::fake('local');
        $job = JobOpening::factory()->create();
        JobApplication::factory()->for($job)->create(['email' => 'ana@exemplo.com']);

        $this->post("/vagas/{$job->slug}/candidatura", [
            'name' => 'Ana Souza',
            'email' => 'ana@exemplo.com',
            'consent' => '1',
            'resume' => UploadedFile::fake()->create('curriculo.pdf', 200, 'application/pdf'),
        ])->assertSessionHasErrors('email');
    }

    #[Test]
    public function nao_da_para_se_candidatar_a_vaga_fora_do_ar(): void
    {
        Storage::fake('local');
        $job = JobOpening::factory()->pending()->create();

        $this->post("/vagas/{$job->slug}/candidatura", [
            'name' => 'Ana Souza',
            'email' => 'ana@exemplo.com',
            'consent' => '1',
            'resume' => UploadedFile::fake()->create('curriculo.pdf', 200, 'application/pdf'),
        ])->assertNotFound();
    }

    #[Test]
    public function so_a_empresa_dona_baixa_o_curriculo(): void
    {
        Storage::fake('local');
        [$company, $user] = $this->empresa();

        $job = JobOpening::factory()->for($company)->create();
        $application = JobApplication::factory()->for($job)->create([
            'resume_path' => 'job-applications/1/cv.pdf',
        ]);
        Storage::disk('local')->put('job-applications/1/cv.pdf', 'conteudo');

        $this->actingAs($user)
            ->get("/empresa/vagas/{$job->id}/candidaturas/{$application->id}/curriculo")
            ->assertOk();

        // Outra empresa nao alcanca nem a vaga, nem o arquivo.
        [, $outra] = $this->empresa();
        $this->actingAs($outra)
            ->get("/empresa/vagas/{$job->id}/candidaturas/{$application->id}/curriculo")
            ->assertNotFound();
    }

    #[Test]
    public function remover_a_candidatura_apaga_o_curriculo_do_disco(): void
    {
        Storage::fake('local');
        [$company, $user] = $this->empresa();

        $job = JobOpening::factory()->for($company)->create();
        $application = JobApplication::factory()->for($job)->create([
            'resume_path' => 'job-applications/1/cv.pdf',
        ]);
        Storage::disk('local')->put('job-applications/1/cv.pdf', 'conteudo');

        $this->actingAs($user)
            ->delete("/empresa/vagas/{$job->id}/candidaturas/{$application->id}")
            ->assertRedirect();

        Storage::disk('local')->assertMissing('job-applications/1/cv.pdf');
        $this->assertSame(0, JobApplication::count());
    }

    #[Test]
    public function apagar_a_vaga_de_vez_leva_os_curriculos_junto(): void
    {
        Storage::fake('local');
        $job = JobOpening::factory()->create();
        JobApplication::factory()->for($job)->create(['resume_path' => 'job-applications/1/cv.pdf']);
        Storage::disk('local')->put('job-applications/1/cv.pdf', 'conteudo');

        $job->forceDelete();

        Storage::disk('local')->assertMissing('job-applications/1/cv.pdf');
        $this->assertSame(0, JobApplication::count());
    }

    // ----- Avisos por e-mail para a empresa -----

    #[Test]
    public function aprovar_avisa_a_empresa_por_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $company = Company::factory()->create(['email' => 'contato@empresa.com']);
        $job = JobOpening::factory()->for($company)->pending()->create();

        $this->actingAs($admin)->patch("/admin/vagas/{$job->id}/aprovar")->assertRedirect();

        Mail::assertSent(JobStatusMail::class, function (JobStatusMail $mail) use ($job) {
            return $mail->hasTo('contato@empresa.com')
                && $mail->event === JobStatusMail::APPROVED
                && $mail->job->is($job);
        });
    }

    #[Test]
    public function rejeitar_avisa_a_empresa_com_o_motivo(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $company = Company::factory()->create(['email' => 'contato@empresa.com']);
        $job = JobOpening::factory()->for($company)->pending()->create();

        $this->actingAs($admin)
            ->patch("/admin/vagas/{$job->id}/rejeitar", ['rejection_reason' => 'Faltou descrever o cargo.'])
            ->assertRedirect();

        Mail::assertSent(JobStatusMail::class, function (JobStatusMail $mail) {
            return $mail->hasTo('contato@empresa.com')
                && $mail->event === JobStatusMail::REJECTED
                && $mail->job->rejection_reason === 'Faltou descrever o cargo.';
        });
    }

    #[Test]
    public function nova_candidatura_avisa_a_empresa(): void
    {
        Mail::fake();
        Storage::fake('local');

        $company = Company::factory()->create(['email' => 'contato@empresa.com']);
        $job = JobOpening::factory()->for($company)->create();

        $this->post("/vagas/{$job->slug}/candidatura", [
            'name' => 'Ana Souza',
            'email' => 'ana@exemplo.com',
            'consent' => '1',
            'resume' => UploadedFile::fake()->create('curriculo.pdf', 200, 'application/pdf'),
        ])->assertRedirect();

        Mail::assertSent(JobApplicationReceivedMail::class,
            fn (JobApplicationReceivedMail $mail) => $mail->hasTo('contato@empresa.com')
        );
    }

    #[Test]
    public function os_corpos_dos_emails_renderizam(): void
    {
        // assertSent() nao renderiza o corpo: um route() quebrado dentro do
        // template so apareceria no envio real. Aqui o corpo e montado de fato.
        $company = Company::factory()->create(['email' => 'contato@empresa.com']);
        $aprovada = JobOpening::factory()->for($company)->create();
        $rejeitada = JobOpening::factory()->for($company)->rejected()->create();

        $corpoAprovada = (new JobStatusMail($aprovada, JobStatusMail::APPROVED, 'Contato'))->render();
        $corpoRejeitada = (new JobStatusMail($rejeitada, JobStatusMail::REJECTED, 'Contato'))->render();

        $this->assertStringContainsString($aprovada->title, $corpoAprovada);
        $this->assertStringContainsString(route('vagas.show', $aprovada), $corpoAprovada);
        $this->assertStringContainsString($rejeitada->rejection_reason, $corpoRejeitada);
    }

    #[Test]
    public function o_aviso_de_candidatura_nao_leva_contato_nem_curriculo(): void
    {
        // E-mail e canal aberto: o dado pessoal fica no painel, que tem
        // controle de acesso.
        $company = Company::factory()->create(['email' => 'contato@empresa.com']);
        $job = JobOpening::factory()->for($company)->create();
        $application = JobApplication::factory()->for($job)->create([
            'name' => 'Ana Souza',
            'email' => 'ana.souza@exemplo.com',
            'phone' => '42988887777',
        ]);

        $corpo = (new JobApplicationReceivedMail($application, 'Contato'))->render();

        $this->assertStringContainsString('Ana Souza', $corpo);
        $this->assertStringNotContainsString('ana.souza@exemplo.com', $corpo);
        $this->assertStringNotContainsString('42988887777', $corpo);
    }

    #[Test]
    public function empresa_sem_email_nao_quebra_a_aprovacao(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $company = Company::factory()->create(['email' => null]);
        $job = JobOpening::factory()->for($company)->pending()->create();

        $this->actingAs($admin)->patch("/admin/vagas/{$job->id}/aprovar")->assertRedirect();

        $this->assertTrue($job->fresh()->isApproved());
        Mail::assertNothingSent();
    }

    #[Test]
    public function a_associacao_nao_ve_os_dados_dos_candidatos(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $job = JobOpening::factory()->create();
        $application = JobApplication::factory()->for($job)->create(['name' => 'Ana Souza']);

        // A tela de analise mostra a contagem, nunca o nome de quem se candidatou.
        $this->actingAs($admin)->get("/admin/vagas/{$job->id}")
            ->assertOk()
            ->assertDontSee($application->name)
            ->assertDontSee($application->email);
    }
}
