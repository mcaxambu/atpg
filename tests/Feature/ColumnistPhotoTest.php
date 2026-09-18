<?php

namespace Tests\Feature;

use App\Actions\SyncColumnistProfile;
use App\Enums\UserRole;
use App\Models\Columnist;
use App\Models\Company;
use App\Models\Member;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Foto propria do colunista, trocada pelo painel dele.
 *
 * Antes a assinatura de uma coluna de empresa mostrava sempre o LOGO ao lado
 * do nome de quem escreveu. O que importa aqui e a foto chegar ao PORTAL, e
 * que remover a foto volte ao comportamento antigo em vez de deixar o
 * colunista sem imagem nenhuma.
 */
class ColumnistPhotoTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: Company, 1: User, 2: Columnist} */
    private function empresaColunista(): array
    {
        $company = Company::factory()->create(['logo_path' => 'logos/dataholds.png']);
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $company->id]);
        $perfil = app(SyncColumnistProfile::class)($company, true, 'Rodrigo Rezende dos Santos');

        return [$company, $user, $perfil];
    }

    private function foto(): UploadedFile
    {
        return UploadedFile::fake()->image('rosto.jpg', 400, 400);
    }

    // ----- Quem nao pode -----

    #[Test]
    public function empresa_que_nao_e_colunista_nao_troca_foto(): void
    {
        Storage::fake('public');

        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $company->id]);

        $this->actingAs($user)
            ->put(route('empresa.colunas.foto.update'), ['photo' => $this->foto()])
            ->assertForbidden();

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    #[Test]
    public function colunista_desativado_nao_troca_foto(): void
    {
        Storage::fake('public');
        [$company, $user] = $this->empresaColunista();

        app(SyncColumnistProfile::class)($company, false);

        $this->actingAs($user)
            ->put(route('empresa.colunas.foto.update'), ['photo' => $this->foto()])
            ->assertForbidden();
    }

    #[Test]
    public function recusa_svg_e_arquivo_que_nao_e_imagem(): void
    {
        // SVG pode carregar script e fica servido no dominio do portal.
        Storage::fake('public');
        [, $user, $perfil] = $this->empresaColunista();

        foreach ([
            UploadedFile::fake()->create('rosto.svg', 4, 'image/svg+xml'),
            UploadedFile::fake()->create('curriculo.pdf', 10, 'application/pdf'),
        ] as $arquivo) {
            $this->actingAs($user)
                ->put(route('empresa.colunas.foto.update'), ['photo' => $arquivo])
                ->assertSessionHasErrors('photo');
        }

        $this->assertNull($perfil->fresh()->custom_photo_path);
        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    #[Test]
    public function recusa_foto_pequena_demais(): void
    {
        // Recortada em circulo e ampliada no portal, uma miniatura fica borrada.
        Storage::fake('public');
        [, $user] = $this->empresaColunista();

        $this->actingAs($user)
            ->put(route('empresa.colunas.foto.update'), ['photo' => UploadedFile::fake()->image('mini.jpg', 60, 60)])
            ->assertSessionHasErrors('photo');
    }

    // ----- O caminho feliz -----

    #[Test]
    public function a_foto_enviada_substitui_o_logo_no_portal(): void
    {
        Storage::fake('public');
        [, $user, $perfil] = $this->empresaColunista();

        // Antes: a assinatura mostra o logo da empresa.
        $this->assertSame('logos/dataholds.png', $perfil->photo_path);

        $this->actingAs($user)
            ->put(route('empresa.colunas.foto.update'), ['photo' => $this->foto()])
            ->assertRedirect(route('empresa.colunas.index'));

        $perfil->refresh();

        $this->assertTrue($perfil->hasCustomPhoto());
        $this->assertSame($perfil->custom_photo_path, $perfil->photo_path);
        Storage::disk('public')->assertExists($perfil->custom_photo_path);

        // E o que o leitor ve: a pagina da coluna traz a foto, nao o logo.
        $coluna = Post::factory()->create(['columnist_id' => $perfil->id]);

        $this->get(route('posts.show', $coluna))
            ->assertOk()
            ->assertSee('storage/'.$perfil->custom_photo_path, false)
            ->assertDontSee('logos/dataholds.png', false);
    }

    #[Test]
    public function trocar_a_foto_apaga_a_anterior(): void
    {
        Storage::fake('public');
        [, $user, $perfil] = $this->empresaColunista();

        $this->actingAs($user)->put(route('empresa.colunas.foto.update'), ['photo' => $this->foto()]);
        $primeira = $perfil->fresh()->custom_photo_path;

        $this->actingAs($user)->put(route('empresa.colunas.foto.update'), ['photo' => $this->foto()]);
        $segunda = $perfil->fresh()->custom_photo_path;

        $this->assertNotSame($primeira, $segunda);
        Storage::disk('public')->assertMissing($primeira);
        Storage::disk('public')->assertExists($segunda);
    }

    #[Test]
    public function remover_a_foto_volta_ao_logo(): void
    {
        Storage::fake('public');
        [, $user, $perfil] = $this->empresaColunista();

        $this->actingAs($user)->put(route('empresa.colunas.foto.update'), ['photo' => $this->foto()]);
        $arquivo = $perfil->fresh()->custom_photo_path;

        $this->actingAs($user)
            ->delete(route('empresa.colunas.foto.destroy'))
            ->assertRedirect(route('empresa.colunas.index'))
            ->assertSessionHas('status', fn (string $aviso) => str_contains($aviso, 'logo da empresa'));

        $perfil->refresh();

        $this->assertFalse($perfil->hasCustomPhoto());
        $this->assertSame('logos/dataholds.png', $perfil->photo_path);
        Storage::disk('public')->assertMissing($arquivo);
    }

    #[Test]
    public function o_membro_colunista_tambem_troca_a_foto(): void
    {
        Storage::fake('public');

        $member = Member::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Member, 'member_id' => $member->id]);
        $perfil = app(SyncColumnistProfile::class)($member, true);

        $this->actingAs($user)
            ->put(route('membro.colunas.foto.update'), ['photo' => $this->foto()])
            ->assertRedirect(route('membro.colunas.index'));

        $this->assertTrue($perfil->fresh()->hasCustomPhoto());
    }

    #[Test]
    public function a_tela_de_colunas_mostra_o_quadro_da_foto(): void
    {
        [, $user] = $this->empresaColunista();

        $this->actingAs($user)
            ->get(route('empresa.colunas.index'))
            ->assertOk()
            ->assertSee('Sua foto de colunista')
            ->assertSee('Hoje: o logo da empresa');
    }

    // ----- A limpeza da apresentacao, que faltava -----

    #[Test]
    public function a_apresentacao_do_colunista_e_limpa_ao_gravar(): void
    {
        [, , $perfil] = $this->empresaColunista();

        $perfil->update(['bio' => '<p>Quem sou</p><script>alert(1)</script>']);

        $this->assertSame('<p>Quem sou</p>', $perfil->fresh()->bio);
    }
}
