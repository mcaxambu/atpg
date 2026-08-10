<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginRouteTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function o_endereco_neutro_abre_a_tela_de_acesso(): void
    {
        $this->get(route('entrar'))
            ->assertOk()
            ->assertSee('Entrar')
            ->assertSee('Empresa associada', false);
    }

    #[Test]
    public function o_menu_do_portal_aponta_para_o_endereco_neutro(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('entrar'), false);
    }

    #[Test]
    public function a_empresa_entra_e_cai_no_painel_dela(): void
    {
        $company = Company::factory()->create();
        User::factory()->forCompany($company)->create([
            'email' => 'empresa@exemplo.test',
            'password' => 'senhaValida1',
        ]);

        $this->post(route('entrar.store'), [
            'email' => 'empresa@exemplo.test',
            'password' => 'senhaValida1',
        ])->assertRedirect(route('empresa.dashboard'));
    }

    #[Test]
    public function o_administrador_entra_e_cai_no_painel_administrativo(): void
    {
        User::factory()->create([
            'email' => 'admin@exemplo.test',
            'password' => 'senhaValida1',
        ]);

        $this->post(route('entrar.store'), [
            'email' => 'admin@exemplo.test',
            'password' => 'senhaValida1',
        ])->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function o_endereco_antigo_continua_funcionando(): void
    {
        // Links antigos e favoritos nao podem quebrar.
        $this->get(route('admin.login'))->assertOk();
        $this->get('/login')->assertRedirect(route('entrar'));
    }

    #[Test]
    public function quem_ja_entrou_vai_para_o_painel_e_nao_para_a_home(): void
    {
        // Antes caia na home e o clique em "Entrar" parecia nao funcionar.
        $this->actingAs(User::factory()->create())
            ->get(route('entrar'))
            ->assertRedirect(route('admin.dashboard'));

        $company = Company::factory()->create();

        $this->actingAs(User::factory()->forCompany($company)->create())
            ->get(route('entrar'))
            ->assertRedirect(route('empresa.dashboard'));
    }

    #[Test]
    public function o_menu_troca_entrar_por_meu_painel_quando_autenticado(): void
    {
        $this->get(route('home'))->assertSee('Entrar')->assertDontSee('Meu painel');

        $this->actingAs(User::factory()->create())
            ->get(route('home'))
            ->assertSee('Meu painel')
            ->assertSee(route('admin.dashboard'), false);
    }

    #[Test]
    public function visitante_em_rota_protegida_vai_para_o_endereco_neutro(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('entrar'));
        $this->get(route('empresa.dashboard'))->assertRedirect(route('entrar'));
    }
}
