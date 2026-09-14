<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Permissao por modulo no painel e o isolamento dos papeis empresa e membro.
 */
class ModuleAccessTest extends TestCase
{
    use RefreshDatabase;

    /** Modulo => uma rota que so ele alcanca. */
    private const ROTAS = [
        'members' => '/admin/membros',
        'companies' => '/admin/empresas',
        'meetings' => '/admin/reunioes',
        'minutes' => '/admin/atas',
        'specialties' => '/admin/especialidades',
        'cms' => '/admin/cms',
        'users' => '/admin/usuarios',
    ];

    private function restrito(array $modulos): User
    {
        return User::factory()->create([
            'role' => UserRole::Admin,
            'abilities' => $modulos,
        ]);
    }

    #[Test]
    public function admin_sem_lista_de_modulos_mantem_acesso_total(): void
    {
        // Estado de todo admin criado antes da coluna existir.
        $user = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->assertTrue($user->hasFullAccess());

        foreach (self::ROTAS as $rota) {
            $this->actingAs($user)->get($rota)->assertOk();
        }
    }

    #[Test]
    public function admin_restrito_entra_so_nos_modulos_liberados(): void
    {
        $user = $this->restrito(['members', 'specialties']);

        $this->actingAs($user)->get('/admin/membros')->assertOk();
        $this->actingAs($user)->get('/admin/especialidades')->assertOk();
    }

    #[Test]
    public function admin_restrito_leva_403_no_modulo_bloqueado(): void
    {
        $user = $this->restrito(['members']);

        foreach (['companies', 'meetings', 'minutes', 'specialties', 'cms', 'users'] as $modulo) {
            $this->actingAs($user)->get(self::ROTAS[$modulo])->assertForbidden();
        }
    }

    #[Test]
    public function dashboard_e_perfil_ficam_livres_para_qualquer_admin(): void
    {
        $user = $this->restrito(['members']);

        $this->actingAs($user)->get('/admin')->assertOk();
        $this->actingAs($user)->get('/admin/perfil')->assertOk();
    }

    #[Test]
    public function o_menu_esconde_o_que_o_usuario_nao_acessa(): void
    {
        $user = $this->restrito(['members']);

        $this->actingAs($user)->get('/admin')
            ->assertSee('/admin/membros')
            ->assertDontSee('/admin/empresas')
            ->assertDontSee('/admin/usuarios');
    }

    #[Test]
    public function admin_restrito_nao_pode_se_promover_a_acesso_total(): void
    {
        // O modulo "users" seria a porta: sem esta trava, ele se autopromove.
        $user = $this->restrito(['users']);

        $this->actingAs($user)->put("/admin/usuarios/{$user->id}", [
            'name' => $user->name,
            'email' => $user->email,
            'role' => UserRole::Admin->value,
            'full_access' => '1',
        ])->assertRedirect();

        $this->assertFalse($user->fresh()->hasFullAccess());
        $this->assertSame(['users'], $user->fresh()->abilities);
    }

    #[Test]
    public function admin_com_acesso_total_pode_restringir_outro_usuario(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $alvo = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)->put("/admin/usuarios/{$alvo->id}", [
            'name' => $alvo->name,
            'email' => $alvo->email,
            'role' => UserRole::Admin->value,
            'abilities' => ['members', 'cms'],
        ])->assertRedirect('/admin/usuarios');

        $this->assertSame(['members', 'cms'], $alvo->fresh()->abilities);
        $this->assertFalse($alvo->fresh()->hasFullAccess());
    }

    #[Test]
    public function admin_precisa_de_ao_menos_um_modulo_ou_acesso_total(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)->post('/admin/usuarios', [
            'name' => 'Sem Nada',
            'email' => 'semnada@teste.local',
            'password' => 'teste12345',
            'password_confirmation' => 'teste12345',
            'role' => UserRole::Admin->value,
        ])->assertSessionHasErrors('abilities');
    }

    #[Test]
    public function usuario_empresa_exige_vinculo_com_empresa(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)->post('/admin/usuarios', [
            'name' => 'Responsavel',
            'email' => 'resp@teste.local',
            'password' => 'teste12345',
            'password_confirmation' => 'teste12345',
            'role' => UserRole::Company->value,
        ])->assertSessionHasErrors('company_id');
    }

    #[Test]
    public function usuario_membro_exige_vinculo_com_cadastro(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)->post('/admin/usuarios', [
            'name' => 'Profissional',
            'email' => 'prof@teste.local',
            'password' => 'teste12345',
            'password_confirmation' => 'teste12345',
            'role' => UserRole::Member->value,
        ])->assertSessionHasErrors('member_id');
    }

    #[Test]
    public function trocar_o_papel_limpa_o_vinculo_que_nao_serve_mais(): void
    {
        $company = Company::factory()->create();
        $member = Member::factory()->create();

        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $alvo = User::factory()->create([
            'role' => UserRole::Company,
            'company_id' => $company->id,
        ]);

        $this->actingAs($admin)->put("/admin/usuarios/{$alvo->id}", [
            'name' => $alvo->name,
            'email' => $alvo->email,
            'role' => UserRole::Member->value,
            'member_id' => $member->id,
        ])->assertRedirect('/admin/usuarios');

        $alvo->refresh();
        $this->assertNull($alvo->company_id);
        $this->assertSame($member->id, $alvo->member_id);
    }

    #[Test]
    public function membro_alcanca_apenas_o_proprio_cadastro(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Member,
            'member_id' => $member->id,
        ]);

        $this->actingAs($user)->get('/membro')->assertOk()->assertSee($member->name);
        $this->actingAs($user)->get('/membro/conta')->assertOk();

        // Nao entra em painel nenhum dos outros papeis.
        $this->actingAs($user)->get('/admin/membros')->assertRedirect();
        $this->actingAs($user)->get('/empresa')->assertRedirect();
    }

    #[Test]
    public function membro_sem_cadastro_vinculado_nao_entra(): void
    {
        $user = User::factory()->create(['role' => UserRole::Member, 'member_id' => null]);

        $this->actingAs($user)->get('/membro')->assertForbidden();
    }

    #[Test]
    public function edicao_do_membro_volta_para_analise(): void
    {
        $member = Member::factory()->create(['is_active' => true]);
        $user = User::factory()->create([
            'role' => UserRole::Member,
            'member_id' => $member->id,
        ]);

        $this->actingAs($user)->put('/membro', [
            'name' => 'Nome Atualizado',
            'email' => $member->email ?: 'novo@teste.local',
        ])->assertRedirect();

        $member->refresh();
        $this->assertSame('Nome Atualizado', $member->name);
        $this->assertTrue($member->isPending());
        $this->assertFalse((bool) $member->is_active);
    }
}
