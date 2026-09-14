<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CmsPage;
use App\Models\Company;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * O editor e um entrypoint proprio do Vite, empurrado por @push('scripts').
 *
 * Ja quebrou uma vez: como import dinamico dentro do app.js, o chunk era
 * buscado em /build/... sem o prefixo da subpasta e dava 404 — o editor
 * simplesmente nao aparecia, sem erro visivel na tela. Estes testes garantem
 * que o asset e referenciado e que os dois layouts tem o stack que o recebe.
 */
class RichEditorAssetTest extends TestCase
{
    use RefreshDatabase;

    private function assertTemEditor(string $url, ?User $como = null): void
    {
        $resposta = $como ? $this->actingAs($como)->get($url) : $this->get($url);
        $html = $resposta->assertOk()->getContent();

        $this->assertStringContainsString(
            'data-editor',
            $html,
            "O campo com editor nao apareceu em: {$url}"
        );

        // Sem o entrypoint carregado, o textarea fica um campo comum.
        $this->assertMatchesRegularExpression(
            '/<script[^>]+src="[^"]*editor[^"]*\.js"/',
            $html,
            "O entrypoint do editor nao foi carregado em: {$url}"
        );
    }

    #[Test]
    public function os_formularios_do_admin_carregam_o_editor(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
        $page = CmsPage::create([
            'title' => 'Sobre', 'slug' => 'sobre',
            'body' => 'Conteúdo institucional da associação.', 'is_published' => true,
        ]);

        $this->assertTemEditor(route('admin.cms.pages.edit', $page), $admin);
        $this->assertTemEditor(route('admin.cms.posts.create'), $admin);
        $this->assertTemEditor(route('admin.cms.events.create'), $admin);
        $this->assertTemEditor(route('admin.companies.create'), $admin);
        $this->assertTemEditor(route('admin.members.create'), $admin);
    }

    #[Test]
    public function os_formularios_da_empresa_carregam_o_editor(): void
    {
        // O layout da empresa nao tinha @stack('scripts'): sem ele o @push
        // some em silencio e o editor nunca carrega.
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Company,
            'company_id' => $company->id,
        ]);

        $this->assertTemEditor(route('empresa.vagas.create'), $user);
        $this->assertTemEditor(route('empresa.perfil.edit'), $user);
        $this->assertTemEditor(route('empresa.membros.create'), $user);
    }

    #[Test]
    public function campo_lido_linha_a_linha_nao_recebe_editor(): void
    {
        // Requisitos e beneficios sao quebrados por linha no PHP; formatar
        // ali estragaria a lista.
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::Company,
            'company_id' => $company->id,
        ]);
        JobOpening::factory()->for($company)->create();

        $html = $this->actingAs($user)->get(route('empresa.vagas.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="description" data-editor/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="requirements"[^>]*data-editor/', $html);
        $this->assertDoesNotMatchRegularExpression('/name="benefits"[^>]*data-editor/', $html);
    }
}
