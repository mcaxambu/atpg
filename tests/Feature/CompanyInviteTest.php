<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use App\Notifications\CompanyAccessInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyInviteTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    #[Test]
    public function envia_o_convite_e_cria_o_acesso(): void
    {
        Notification::fake();
        $company = Company::factory()->create(['email' => 'contato@alpha.test']);

        $this->actingAs($this->admin())
            ->post(route('admin.companies.invite', $company))
            ->assertRedirect()
            ->assertSessionHas('status');

        $user = User::where('email', 'contato@alpha.test')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Company, $user->role);
        $this->assertSame($company->id, $user->company_id);
        $this->assertNotNull($user->invited_at);

        Notification::assertSentTo($user, CompanyAccessInvitation::class);
    }

    #[Test]
    public function recusa_convite_para_empresa_nao_publicada(): void
    {
        Notification::fake();
        $company = Company::factory()->pending()->create(['email' => 'contato@alpha.test']);

        $this->actingAs($this->admin())
            ->post(route('admin.companies.invite', $company))
            ->assertSessionHasErrors('invite');

        Notification::assertNothingSent();
    }

    #[Test]
    public function recusa_convite_sem_email(): void
    {
        Notification::fake();
        $company = Company::factory()->create(['email' => null]);

        $this->actingAs($this->admin())
            ->post(route('admin.companies.invite', $company))
            ->assertSessionHasErrors('invite');

        Notification::assertNothingSent();
    }

    #[Test]
    public function nao_reenvia_para_quem_ja_tem_acesso_ativo(): void
    {
        Notification::fake();
        $company = Company::factory()->create(['email' => 'contato@alpha.test']);
        User::factory()->forCompany($company)->create(['email' => 'contato@alpha.test']);

        $this->actingAs($this->admin())
            ->post(route('admin.companies.invite', $company))
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    #[Test]
    public function reenvia_quando_o_administrador_forca(): void
    {
        Notification::fake();
        $company = Company::factory()->create(['email' => 'contato@alpha.test']);
        $user = User::factory()->forCompany($company)->create(['email' => 'contato@alpha.test']);

        $this->actingAs($this->admin())
            ->post(route('admin.companies.invite', $company), ['force' => '1'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, CompanyAccessInvitation::class);
    }

    #[Test]
    public function recusa_quando_o_email_pertence_a_outro_usuario(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['email' => 'chefe@atpg.test']);
        $company = Company::factory()->create(['email' => 'chefe@atpg.test']);

        $this->actingAs($this->admin())
            ->post(route('admin.companies.invite', $company))
            ->assertSessionHasErrors('invite');

        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
        Notification::assertNothingSent();
    }

    /**
     * O envio do convite acontece dentro da aprovacao. Se o servidor de e-mail
     * estiver fora do ar, a excecao do transporte nao pode virar erro 500 e
     * deixar o administrador sem saber se a empresa foi aprovada — ela ja foi,
     * a gravacao acontece antes do e-mail.
     */
    #[Test]
    public function falha_no_servidor_de_email_nao_derruba_a_aprovacao(): void
    {
        $this->quebrarOEnvioDeEmail();
        $company = Company::factory()->pending()->create(['email' => 'contato@alpha.test']);

        $this->actingAs($this->admin())
            ->patch(route('admin.companies.approve', $company))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertTrue($company->fresh()->isVisible());
        $this->assertNotNull(User::where('email', 'contato@alpha.test')->first());
    }

    #[Test]
    public function falha_no_servidor_de_email_nao_derruba_o_convite_manual(): void
    {
        $this->quebrarOEnvioDeEmail();
        $company = Company::factory()->create(['email' => 'contato@alpha.test']);

        $this->actingAs($this->admin())
            ->post(route('admin.companies.invite', $company))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        // O acesso fica criado e o convite pode ser reenviado quando o e-mail
        // voltar; o que nao pode e a tela quebrar.
        $this->assertNotNull(User::where('email', 'contato@alpha.test')->first());
    }

    /**
     * Simula o servidor de e-mail indisponivel: o transporte SMTP aponta para
     * uma porta fechada em loopback e estoura na hora do envio.
     */
    private function quebrarOEnvioDeEmail(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1,
            'mail.mailers.smtp.timeout' => 1,
        ]);
    }

    #[Test]
    public function a_tela_da_empresa_mostra_o_botao(): void
    {
        $company = Company::factory()->create(['email' => 'contato@alpha.test']);

        $this->actingAs($this->admin())
            ->get(route('admin.companies.show', $company))
            ->assertOk()
            ->assertSee('Acesso ao painel')
            ->assertSee('Enviar convite');
    }
}
