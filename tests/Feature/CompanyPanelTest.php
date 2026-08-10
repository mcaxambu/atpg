<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Member;
use App\Models\User;
use App\Notifications\CompanyAccessInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyPanelTest extends TestCase
{
    use RefreshDatabase;

    private function companyUser(?Company $company = null): array
    {
        $company ??= Company::factory()->create();

        return [$company, User::factory()->forCompany($company)->create()];
    }

    #[Test]
    public function aprovar_empresa_cria_o_acesso_e_envia_o_convite(): void
    {
        Mail::fake();
        Notification::fake();

        $company = Company::factory()->pending()->create(['email' => 'contato@nova.test']);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.companies.approve', $company))
            ->assertRedirect();

        $user = User::where('email', 'contato@nova.test')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Company, $user->role);
        $this->assertSame($company->id, $user->company_id);
        $this->assertFalse($user->hasActivatedAccess());

        Notification::assertSentTo($user, CompanyAccessInvitation::class);
    }

    #[Test]
    public function nao_sequestra_um_email_que_ja_e_de_administrador(): void
    {
        Mail::fake();
        Notification::fake();

        $admin = User::factory()->create(['email' => 'chefe@atpg.test']);
        $company = Company::factory()->pending()->create(['email' => 'chefe@atpg.test']);

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.companies.approve', $company));

        $admin->refresh();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertNull($admin->company_id);
        Notification::assertNothingSent();
    }

    #[Test]
    public function o_painel_da_empresa_responde(): void
    {
        [, $user] = $this->companyUser();

        foreach ([
            route('empresa.dashboard'),
            route('empresa.perfil.edit'),
            route('empresa.conta.edit'),
            route('empresa.membros.index'),
            route('empresa.membros.create'),
        ] as $url) {
            $this->actingAs($user)->get($url)->assertOk("Falhou em {$url}");
        }
    }

    #[Test]
    public function empresa_nao_entra_no_painel_administrativo(): void
    {
        [, $user] = $this->companyUser();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('empresa.dashboard'));

        $this->actingAs($user)
            ->get(route('admin.companies.pending'))
            ->assertRedirect(route('empresa.dashboard'));
    }

    #[Test]
    public function administrador_nao_entra_no_painel_da_empresa(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('empresa.dashboard'))
            ->assertRedirect(route('admin.dashboard'));
    }

    #[Test]
    public function a_empresa_so_enxerga_os_proprios_colaboradores(): void
    {
        [$minha, $user] = $this->companyUser();
        $meu = Member::factory()->for($minha)->create(['name' => 'Colaborador Meu']);

        $outra = Company::factory()->create();
        $alheio = Member::factory()->for($outra)->create(['name' => 'Colaborador Alheio']);

        $this->actingAs($user)
            ->get(route('empresa.membros.index'))
            ->assertOk()
            ->assertSee('Colaborador Meu')
            ->assertDontSee('Colaborador Alheio');

        // Trocar o id na URL nao alcanca o colaborador de outra empresa.
        $this->actingAs($user)->get(route('empresa.membros.edit', $alheio->id))->assertNotFound();
        $this->actingAs($user)->delete(route('empresa.membros.destroy', $alheio->id))->assertNotFound();
        $this->actingAs($user)->get(route('empresa.membros.edit', $meu->id))->assertOk();
    }

    #[Test]
    public function colaborador_cadastrado_pela_empresa_entra_como_pendente(): void
    {
        [$company, $user] = $this->companyUser();

        $this->actingAs($user)->post(route('empresa.membros.store'), [
            'name' => 'Ana Souza',
            'email' => 'ana@empresa.test',
            'role' => 'Desenvolvedora',
        ])->assertRedirect(route('empresa.membros.index'));

        $member = Member::firstWhere('name', 'Ana Souza');

        $this->assertSame($company->id, $member->company_id);
        $this->assertSame(ModerationStatus::Pending, $member->status);
        $this->assertFalse($member->is_active);
        $this->assertSame('company', $member->registration_source);

        // Cai na fila que a associacao ja usa.
        $this->actingAs(User::factory()->create())
            ->get(route('admin.members.pending'))
            ->assertOk()
            ->assertSee('Ana Souza');
    }

    #[Test]
    public function editar_colaborador_aprovado_devolve_para_analise(): void
    {
        [$company, $user] = $this->companyUser();
        $member = Member::factory()->for($company)->create(['name' => 'Ana Souza', 'email' => 'ana@empresa.test']);

        $this->assertTrue($member->isVisible());

        $this->actingAs($user)->put(route('empresa.membros.update', $member->id), [
            'name' => 'Ana Souza Lima',
            'email' => 'ana@empresa.test',
        ])->assertRedirect();

        $member->refresh();

        $this->assertSame(ModerationStatus::Pending, $member->status);
        $this->assertFalse($member->is_active);
    }

    #[Test]
    public function a_empresa_edita_os_proprios_dados_e_publica_direto(): void
    {
        [$company, $user] = $this->companyUser();

        $this->actingAs($user)->put(route('empresa.perfil.update'), [
            'segment' => 'Software sob medida',
            'description' => 'Desenvolvemos sistemas para o varejo regional.',
            'city' => 'Ponta Grossa',
            'state' => 'pr',
        ])->assertRedirect();

        $company->refresh();

        $this->assertSame('Software sob medida', $company->segment);
        $this->assertSame('PR', $company->state);
        $this->assertSame(ModerationStatus::Approved, $company->status);
        $this->assertTrue($company->is_active);
    }

    #[Test]
    public function a_empresa_nao_altera_nome_nem_cnpj(): void
    {
        [$company, $user] = $this->companyUser();
        $nomeOriginal = $company->name;
        $cnpjOriginal = $company->cnpj;

        $this->actingAs($user)->put(route('empresa.perfil.update'), [
            'name' => 'Outra Empresa',
            'cnpj' => '11.222.333/0001-81',
            'segment' => 'Software',
        ])->assertRedirect();

        $company->refresh();

        $this->assertSame($nomeOriginal, $company->name);
        $this->assertSame($cnpjOriginal, $company->cnpj);
    }
}
