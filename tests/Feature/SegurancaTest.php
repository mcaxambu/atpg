<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Defesas do portal.
 *
 * Cada teste aqui guarda uma porta que estava aberta em 11/11/2026, quando o
 * usuario pediu o sistema protegido contra invasao. Sao defesas baratas de
 * manter e caras de perceber quando somem — por isso ficam amarradas em teste.
 */
class SegurancaTest extends TestCase
{
    use RefreshDatabase;

    // ----- Cabeçalhos -----

    #[Test]
    public function a_resposta_leva_os_cabecalhos_de_seguranca(): void
    {
        $resposta = $this->get(route('home'));

        $resposta->assertHeader('X-Content-Type-Options', 'nosniff');
        $resposta->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $resposta->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertStringContainsString("default-src 'self'", $resposta->headers->get('Content-Security-Policy'));
    }

    #[Test]
    public function a_politica_permite_o_que_o_portal_usa_de_fora(): void
    {
        // Sem estas origens o editor de textos, as fontes e o vídeo embutido
        // parariam de carregar — e o estrago apareceria só em produção.
        $csp = $this->get(route('home'))->headers->get('Content-Security-Policy');

        $this->assertStringContainsString('cdn.jsdelivr.net', $csp);
        $this->assertStringContainsString('fonts.gstatic.com', $csp);
        $this->assertStringContainsString('youtube.com', $csp);
        $this->assertStringContainsString('viacep.com.br', $csp);
    }

    // ----- Força bruta -----

    #[Test]
    public function a_conta_trava_depois_de_cinco_tentativas_erradas(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'alvo@atpg.net.br',
            'password' => 'senha-certa-123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.store'), [
                'email' => $user->email,
                'password' => 'chute-errado',
            ]);
        }

        // A senha CERTA tambem para de valer: senao a trava nao trava nada.
        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'senha-certa-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    #[Test]
    public function a_trava_e_por_conta_e_ip_e_nao_atinge_quem_acertou(): void
    {
        $vitima = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'vitima@atpg.net.br',
            'password' => 'senha-certa-123',
        ]);

        $outro = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'outro@atpg.net.br',
            'password' => 'senha-dele-456',
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->post(route('admin.login.store'), [
                'email' => $vitima->email,
                'password' => 'chute-errado',
            ]);
        }

        // Travar a conta de alguem errando a senha dele de proposito seria um
        // jeito de derrubar o acesso dos outros.
        $this->post(route('admin.login.store'), [
            'email' => $outro->email,
            'password' => 'senha-dele-456',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($outro);
    }

    #[Test]
    public function acertar_a_senha_zera_a_contagem(): void
    {
        $user = User::factory()->create([
            'role' => UserRole::Admin,
            'email' => 'certo@atpg.net.br',
            'password' => 'senha-certa-123',
        ]);

        foreach (['errada', 'errada'] as $chute) {
            $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => $chute]);
        }

        $this->post(route('admin.login.store'), [
            'email' => $user->email,
            'password' => 'senha-certa-123',
        ])->assertRedirect();

        $this->assertSame(0, RateLimiter::attempts('login:'.$user->email.'|127.0.0.1'));
    }

    // ----- Envio de arquivo -----

    #[Test]
    public function o_cadastro_publico_recusa_logo_em_svg(): void
    {
        /*
         * SVG e texto, e texto pode carregar <script>. Servido do nosso
         * proprio dominio, abrir o arquivo executaria esse script na origem
         * do portal — onde esta o cookie de sessao de quem ja entrou. E o
         * caminho mais barato de um XSS armazenado virar sessao roubada, e
         * este formulario e publico, sem login nenhum.
         */
        $resposta = $this->post(route('companies.register.store'), [
            'name' => 'Empresa Teste',
            'legal_name' => 'Empresa Teste LTDA',
            'email' => 'contato@empresateste.com.br',
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ]);

        $resposta->assertSessionHasErrors('logo');
    }
}
