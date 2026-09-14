<?php

namespace Tests\Feature;

use App\Actions\SyncColumnistProfile;
use App\Enums\ModerationStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Member;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Moderacao das colunas delegada a uma empresa.
 *
 * O painel da empresa mostra apenas os dados dela; esta e a unica excecao a
 * esse limite, e existe so quando a diretoria marca `moderates_columns`. Os
 * testes aqui existem sobretudo para provar o NEGATIVO: empresa sem a marcacao
 * nao alcanca nada disso.
 */
class DelegatedColumnModerationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Company, 1: User} */
    private function empresa(bool $modera = false): array
    {
        $company = Company::factory()->create(['moderates_columns' => $modera]);
        $user = User::factory()->create([
            'role' => UserRole::Company,
            'company_id' => $company->id,
        ]);

        return [$company, $user];
    }

    private function colunaPendente(): Post
    {
        $member = Member::factory()->create(['name' => 'Ana Souza']);
        $perfil = app(SyncColumnistProfile::class)($member, true);

        return Post::factory()->pending()->create([
            'columnist_id' => $perfil->id,
            'title' => 'Coluna aguardando decisão',
        ]);
    }

    // ----- O negativo: quem não foi delegado -----

    #[Test]
    public function empresa_sem_a_marcacao_nao_alcanca_a_moderacao(): void
    {
        [, $user] = $this->empresa(modera: false);
        $coluna = $this->colunaPendente();

        $this->actingAs($user)->get('/empresa/moderacao/colunas')->assertForbidden();
        $this->actingAs($user)->get("/empresa/moderacao/colunas/{$coluna->id}")->assertForbidden();
        $this->actingAs($user)->patch("/empresa/moderacao/colunas/{$coluna->id}/aprovar")->assertForbidden();
        $this->actingAs($user)->patch("/empresa/moderacao/colunas/{$coluna->id}/rejeitar")->assertForbidden();

        // E o mais importante: a coluna continua intacta.
        $this->assertTrue($coluna->fresh()->isPending());
    }

    #[Test]
    public function o_menu_da_empresa_so_mostra_a_moderacao_para_a_delegada(): void
    {
        [, $semPermissao] = $this->empresa(modera: false);
        [, $comPermissao] = $this->empresa(modera: true);

        $this->actingAs($semPermissao)->get('/empresa')->assertOk()->assertDontSee('Moderar colunas');
        $this->actingAs($comPermissao)->get('/empresa')->assertOk()->assertSee('Moderar colunas');
    }

    #[Test]
    public function tirar_a_marcacao_corta_o_acesso_na_hora(): void
    {
        [$company, $user] = $this->empresa(modera: true);

        $this->actingAs($user)->get('/empresa/moderacao/colunas')->assertOk();

        $company->update(['moderates_columns' => false]);

        // `fresh()` modela a requisicao seguinte: o mesmo objeto de usuario
        // guarda a relacao `company` ja carregada, e reusa-lo aqui testaria a
        // memoria do teste, nao o comportamento do sistema.
        $this->actingAs($user->fresh())->get('/empresa/moderacao/colunas')->assertForbidden();
    }

    #[Test]
    public function o_membro_nao_alcanca_a_moderacao_da_empresa(): void
    {
        $member = Member::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Member, 'member_id' => $member->id]);

        // Rota do painel da empresa: outro papel nem chega la.
        $this->actingAs($user)->get('/empresa/moderacao/colunas')->assertRedirect();
    }

    // ----- O positivo: a empresa delegada -----

    #[Test]
    public function a_empresa_delegada_ve_a_fila_e_aprova(): void
    {
        [, $user] = $this->empresa(modera: true);
        $coluna = $this->colunaPendente();

        $this->actingAs($user)->get('/empresa/moderacao/colunas')
            ->assertOk()
            ->assertSee($coluna->title)
            ->assertSee('Ana Souza');

        $this->actingAs($user)->patch("/empresa/moderacao/colunas/{$coluna->id}/aprovar")->assertRedirect();

        $coluna->refresh();

        $this->assertTrue($coluna->isApproved());
        $this->assertTrue((bool) $coluna->is_published);
        $this->get(route('posts.show', $coluna))->assertOk();
    }

    #[Test]
    public function a_empresa_delegada_devolve_com_motivo(): void
    {
        [, $user] = $this->empresa(modera: true);
        $coluna = $this->colunaPendente();

        $this->actingAs($user)
            ->patch("/empresa/moderacao/colunas/{$coluna->id}/rejeitar", ['rejection_reason' => 'Falta fonte para o dado citado.'])
            ->assertRedirect();

        $coluna->refresh();

        $this->assertTrue($coluna->isRejected());
        $this->assertSame('Falta fonte para o dado citado.', $coluna->rejection_reason);
        $this->assertFalse((bool) $coluna->is_published);
    }

    #[Test]
    public function a_moderacao_registra_quem_decidiu(): void
    {
        [, $user] = $this->empresa(modera: true);
        $coluna = $this->colunaPendente();

        $this->actingAs($user)->patch("/empresa/moderacao/colunas/{$coluna->id}/aprovar");

        $this->assertSame($user->id, $coluna->fresh()->reviewed_by);
    }

    #[Test]
    public function a_delegada_nao_modera_noticia_da_diretoria(): void
    {
        // A excecao e para COLUNAS. Noticia institucional continua sendo
        // assunto da diretoria, mesmo para a empresa delegada.
        [, $user] = $this->empresa(modera: true);

        $noticia = Post::factory()->create(['columnist_id' => null]);

        $this->actingAs($user)->get("/empresa/moderacao/colunas/{$noticia->id}")->assertNotFound();
        $this->actingAs($user)->patch("/empresa/moderacao/colunas/{$noticia->id}/aprovar")->assertNotFound();
    }

    #[Test]
    public function a_delegacao_continua_valendo_para_a_diretoria(): void
    {
        // Delegar nao tira o poder de quem delegou.
        $this->empresa(modera: true);
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $coluna = $this->colunaPendente();

        $this->actingAs($admin)->patch("/admin/colunas/{$coluna->id}/aprovar")->assertRedirect();

        $this->assertTrue($coluna->fresh()->isApproved());
    }

    #[Test]
    public function a_marcacao_e_gravada_pelo_formulario_do_admin(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $company = Company::factory()->create(['moderates_columns' => false]);

        $base = [
            'name' => $company->name,
            'status' => ModerationStatus::Approved->value,
            'is_active' => '1',
        ];

        $this->actingAs($admin)
            ->put(route('admin.companies.update', $company), $base + ['moderates_columns' => '1'])
            ->assertRedirect();

        $this->assertTrue($company->fresh()->moderates_columns);

        // Caixa desmarcada nao envia nada: precisa voltar a false.
        $this->actingAs($admin)
            ->put(route('admin.companies.update', $company), $base)
            ->assertRedirect();

        $this->assertFalse($company->fresh()->moderates_columns);
    }
}
