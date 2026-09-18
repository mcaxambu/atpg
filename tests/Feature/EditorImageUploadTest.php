<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Envio de imagem de dentro do editor de textos.
 *
 * O campo de texto aceita imagem desde 17/09/2026. Como a rota grava arquivo no
 * disco publico, os testes que mais importam sao os de RECUSA: sem sessao, com
 * arquivo que nao e imagem, e com SVG — que pode carregar script dentro e
 * ficaria servido no proprio dominio do portal.
 */
class EditorImageUploadTest extends TestCase
{
    use RefreshDatabase;

    private function imagem(string $nome = 'foto.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($nome, 800, 600);
    }

    // ----- Quem nao pode -----

    #[Test]
    public function visitante_sem_sessao_nao_envia(): void
    {
        Storage::fake('public');

        $this->postJson(route('editor.imagem'), ['image' => $this->imagem()])
            ->assertUnauthorized();

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    #[Test]
    public function recusa_arquivo_que_nao_e_imagem(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->postJson(route('editor.imagem'), [
                'image' => UploadedFile::fake()->create('planilha.pdf', 10, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    #[Test]
    public function recusa_svg(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->postJson(route('editor.imagem'), [
                'image' => UploadedFile::fake()->create('mapa.svg', 4, 'image/svg+xml'),
            ])
            ->assertUnprocessable();

        $this->assertEmpty(Storage::disk('public')->allFiles());
    }

    #[Test]
    public function recusa_imagem_acima_do_limite(): void
    {
        Storage::fake('public');

        $this->actingAs(User::factory()->create())
            ->postJson(route('editor.imagem'), [
                'image' => UploadedFile::fake()->create('grande.jpg', 6 * 1024, 'image/jpeg'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    }

    // ----- Quem pode -----

    #[Test]
    public function a_diretoria_envia_e_recebe_o_endereco(): void
    {
        Storage::fake('public');

        $resposta = $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->postJson(route('editor.imagem'), ['image' => $this->imagem()])
            ->assertOk()
            ->assertJsonStructure(['url']);

        $arquivos = Storage::disk('public')->allFiles('editor');

        $this->assertCount(1, $arquivos);
        $this->assertStringContainsString('storage/editor/', $resposta->json('url'));
    }

    #[Test]
    public function a_empresa_e_o_membro_tambem_enviam(): void
    {
        // Coluna e vaga sao escritas por eles, no mesmo editor: barrar aqui
        // deixaria o botao de imagem quebrado nos outros dois paineis.
        Storage::fake('public');

        foreach ([UserRole::Company, UserRole::Member] as $papel) {
            $this->actingAs(User::factory()->create(['role' => $papel]))
                ->postJson(route('editor.imagem'), ['image' => $this->imagem()])
                ->assertOk();
        }

        $this->assertCount(2, Storage::disk('public')->allFiles('editor'));
    }
}
