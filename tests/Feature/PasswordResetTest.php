<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function a_tela_de_recuperacao_responde(): void
    {
        $this->get(route('password.request'))->assertOk();
    }

    #[Test]
    public function envia_o_link_de_redefinicao(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    #[Test]
    public function nao_revela_se_o_email_existe(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $existente = $this->post(route('password.email'), ['email' => $user->email]);
        $inexistente = $this->post(route('password.email'), ['email' => 'ninguem@exemplo.test']);

        // Mesma mensagem nos dois casos: a diferenca revelaria quem tem conta.
        $this->assertSame($existente->getSession()->get('status'), $inexistente->getSession()->get('status'));
    }

    #[Test]
    public function redefine_a_senha_e_entra_no_painel(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk();

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'novaSenha123',
            'password_confirmation' => 'novaSenha123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
    }

    #[Test]
    public function o_convidado_da_empresa_define_a_senha_e_cai_no_painel_dela(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->forCompany($company)->invited()->create();

        $this->assertFalse($user->hasActivatedAccess());

        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'primeiraSenha1',
            'password_confirmation' => 'primeiraSenha1',
        ])->assertRedirect(route('empresa.dashboard'));

        $this->assertTrue($user->fresh()->hasActivatedAccess());
    }

    #[Test]
    public function senha_fraca_e_recusada(): void
    {
        $user = User::factory()->create();
        $token = Password::broker()->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'curta',
            'password_confirmation' => 'curta',
        ])->assertSessionHasErrors('password');
    }
}
