<?php

namespace Tests\Feature;

use App\Enums\ModerationStatus;
use App\Http\Controllers\Admin\CmsModuleController;
use App\Models\Company;
use App\Models\Member;
use App\Models\Specialty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    /** CNPJ com dígitos verificadores válidos. */
    private const CNPJ = '11.222.333/0001-81';

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function companyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tech Solutions',
            'legal_name' => 'Tech Solutions LTDA',
            'status' => ModerationStatus::Approved->value,
            'is_active' => '1',
        ], $overrides);
    }

    #[Test]
    public function visitante_e_redirecionado_para_o_login(): void
    {
        // O acesso passou a viver no endereco neutro /entrar, que serve tanto
        // a associacao quanto as empresas.
        $this->get(route('admin.dashboard'))->assertRedirect(route('entrar'));
        $this->get(route('admin.members.pending'))->assertRedirect(route('entrar'));
    }

    #[Test]
    public function duas_empresas_com_o_mesmo_nome_geram_slugs_diferentes(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.companies.store'), $this->companyPayload())
            ->assertRedirect(route('admin.companies.index'));

        // Antes isto estourava com violação da chave única de slug.
        $this->actingAs($admin)->post(route('admin.companies.store'), $this->companyPayload())
            ->assertRedirect(route('admin.companies.index'));

        $slugs = Company::pluck('slug');

        $this->assertCount(2, $slugs);
        $this->assertSame($slugs->unique()->count(), $slugs->count());
    }

    #[Test]
    public function cnpj_invalido_e_recusado(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.companies.store'), $this->companyPayload(['cnpj' => '11.111.111/1111-11']))
            ->assertSessionHasErrors('cnpj');

        $this->actingAs($this->admin())
            ->post(route('admin.companies.store'), $this->companyPayload(['cnpj' => self::CNPJ]))
            ->assertSessionHasNoErrors();
    }

    #[Test]
    public function membro_aceita_upload_de_foto(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.members.store'), [
            'name' => 'Ana Souza',
            'status' => ModerationStatus::Approved->value,
            'is_active' => '1',
            'photo' => UploadedFile::fake()->image('ana.jpg'),
        ])->assertRedirect(route('admin.members.index'));

        $member = Member::firstWhere('name', 'Ana Souza');

        $this->assertNotNull($member->photo_path);
        Storage::disk('public')->assertExists($member->photo_path);
    }

    #[Test]
    public function exclusao_e_logica_e_pode_ser_desfeita(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();

        $this->actingAs($admin)->delete(route('admin.companies.destroy', $company))->assertRedirect();

        $this->assertSoftDeleted($company);
        $this->get(route('companies.show', $company))->assertNotFound();

        $this->actingAs($admin)->patch(route('admin.companies.restore', $company->id))->assertRedirect();

        $this->assertNotSoftDeleted($company->fresh());
        $this->get(route('companies.show', $company))->assertOk();
    }

    #[Test]
    public function busca_e_filtro_por_situacao_funcionam(): void
    {
        Company::factory()->create(['name' => 'Alpha Digital']);
        Company::factory()->pending()->create(['name' => 'Beta Sistemas']);

        $this->actingAs($this->admin())
            ->get(route('admin.companies.index', ['q' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Digital')
            ->assertDontSee('Beta Sistemas');

        $this->actingAs($this->admin())
            ->get(route('admin.companies.index', ['status' => ModerationStatus::Pending->value]))
            ->assertOk()
            ->assertSee('Beta Sistemas')
            ->assertDontSee('Alpha Digital');
    }

    #[Test]
    public function especialidade_com_nome_repetido_e_recusada_sem_estourar(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.specialties.store'), ['name' => 'DevOps'])
            ->assertRedirect(route('admin.specialties.index'));

        $this->actingAs($admin)->post(route('admin.specialties.store'), ['name' => 'DevOps'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Specialty::count());
    }

    #[Test]
    public function especialidade_em_uso_nao_pode_ser_excluida(): void
    {
        $specialty = Specialty::factory()->create();
        Member::factory()->create()->specialties()->attach($specialty);

        $this->actingAs($this->admin())
            ->delete(route('admin.specialties.destroy', $specialty))
            ->assertSessionHasErrors('specialty');

        $this->assertDatabaseHas('specialties', ['id' => $specialty->id]);
    }

    #[Test]
    public function usuario_nao_pode_excluir_a_si_mesmo(): void
    {
        $admin = $this->admin();
        User::factory()->create();

        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    #[Test]
    public function todas_as_telas_do_admin_respondem(): void
    {
        $admin = $this->admin();
        $company = Company::factory()->create();
        $member = Member::factory()->create();
        Specialty::factory()->create();

        $urls = [
            route('admin.dashboard'),
            route('admin.members.index'),
            route('admin.members.pending'),
            route('admin.members.create'),
            route('admin.members.trash'),
            route('admin.members.show', $member),
            route('admin.members.edit', $member),
            route('admin.companies.index'),
            route('admin.companies.pending'),
            route('admin.companies.create'),
            route('admin.companies.trash'),
            route('admin.companies.show', $company),
            route('admin.companies.edit', $company),
            route('admin.specialties.index'),
            route('admin.specialties.create'),
            route('admin.users.index'),
            route('admin.users.create'),
            route('admin.profile.edit'),
            route('admin.cms.dashboard'),
            route('admin.cms.settings.edit'),
            route('admin.cms.posts.index'),
            route('admin.cms.posts.create'),
            route('admin.cms.events.index'),
            route('admin.cms.events.create'),
            route('admin.cms.pages.index'),
            route('admin.cms.pages.create'),
        ];

        foreach (array_keys(CmsModuleController::modules()) as $module) {
            $urls[] = route('admin.cms.modules.index', $module);
            $urls[] = route('admin.cms.modules.create', $module);
        }

        foreach ($urls as $url) {
            $this->actingAs($admin)->get($url)->assertOk("Falhou em {$url}");
        }
    }
}
