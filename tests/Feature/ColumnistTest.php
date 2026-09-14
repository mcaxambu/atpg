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
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Colunistas do portal.
 *
 * O colunista NAO e um usuario novo: e um membro ou uma empresa marcado como
 * colunista no cadastro, que escreve pelo painel que ja usa. Estes testes
 * cobrem a marcacao, quem pode escrever, o isolamento entre autores e o que
 * chega ao site.
 */
class ColumnistTest extends TestCase
{
    use RefreshDatabase;

    private function marcarComoColunista(Member|Company $cadastro, ?string $assinatura = null): Columnist
    {
        return app(SyncColumnistProfile::class)($cadastro, true, $assinatura);
    }

    /** @return array{0: Member, 1: User, 2: Columnist} */
    private function membroColunista(): array
    {
        $member = Member::factory()->create(['name' => 'Ana Souza']);
        $user = User::factory()->create(['role' => UserRole::Member, 'member_id' => $member->id]);

        return [$member, $user, $this->marcarComoColunista($member)];
    }

    /** @return array{0: Company, 1: User, 2: Columnist} */
    private function empresaColunista(): array
    {
        $company = Company::factory()->create(['name' => 'Tech LTDA']);
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $company->id]);

        return [$company, $user, $this->marcarComoColunista($company)];
    }

    private function textoValido(array $overrides = []): array
    {
        return array_merge([
            'title' => 'O que trava a inovação em Ponta Grossa',
            'excerpt' => 'Um diagnóstico do que falta para o ecossistema deslanchar.',
            'body' => str_repeat('Texto suficientemente longo para passar na validação mínima da coluna. ', 5),
        ], $overrides);
    }

    // ----- Marcação no cadastro -----

    #[Test]
    public function marcar_no_cadastro_cria_o_perfil_de_colunista(): void
    {
        $member = Member::factory()->create(['name' => 'Ana Souza']);

        $perfil = $this->marcarComoColunista($member);

        $this->assertSame($member->id, $perfil->member_id);
        $this->assertNull($perfil->company_id);
        $this->assertSame('ana-souza', $perfil->slug);
        $this->assertSame('Ana Souza', $perfil->display_name);
        $this->assertTrue($perfil->is_active);
    }

    #[Test]
    public function desmarcar_desativa_sem_apagar_as_colunas(): void
    {
        [$member, , $perfil] = $this->membroColunista();
        Post::factory()->create(['columnist_id' => $perfil->id]);

        app(SyncColumnistProfile::class)($member, false);

        $this->assertFalse($perfil->fresh()->is_active);
        // O texto continua existindo e apontando para o mesmo perfil: apagar
        // deixaria coluna sem assinatura e mudaria o endereco ao remarcar.
        $this->assertSame(1, Post::where('columnist_id', $perfil->id)->count());
    }

    #[Test]
    public function remarcar_reaproveita_o_mesmo_perfil_e_o_mesmo_endereco(): void
    {
        [$member, , $perfil] = $this->membroColunista();
        $slugOriginal = $perfil->slug;

        app(SyncColumnistProfile::class)($member, false);
        $remarcado = $this->marcarComoColunista($member);

        $this->assertTrue($remarcado->is($perfil));
        $this->assertSame($slugOriginal, $remarcado->slug);
        $this->assertSame(1, Columnist::count());
    }

    #[Test]
    public function assinatura_em_branco_cai_para_o_nome_do_cadastro(): void
    {
        [$member, , $perfil] = $this->membroColunista();

        $this->assertSame($member->name, $perfil->display_name);
    }

    #[Test]
    public function empresa_assina_com_a_pessoa_creditada(): void
    {
        $company = Company::factory()->create(['name' => 'Tech LTDA']);
        $perfil = $this->marcarComoColunista($company, 'João Silva');

        $this->assertSame('João Silva — Tech LTDA', $perfil->byline);
    }

    #[Test]
    public function empresa_sem_pessoa_creditada_assina_com_o_proprio_nome(): void
    {
        $company = Company::factory()->create(['name' => 'Tech LTDA']);

        $this->assertSame('Tech LTDA', $this->marcarComoColunista($company)->byline);
    }

    #[Test]
    public function o_endereco_nao_colide_entre_membro_e_empresa_de_mesmo_nome(): void
    {
        // Slug de membro e de empresa vivem em tabelas diferentes e podem
        // repetir; aqui a unicidade e do colunista.
        $member = Member::factory()->create(['name' => 'Alfa']);
        $company = Company::factory()->create(['name' => 'Alfa']);

        $doMembro = $this->marcarComoColunista($member);
        $daEmpresa = $this->marcarComoColunista($company);

        $this->assertNotSame($doMembro->slug, $daEmpresa->slug);
    }

    // ----- Escrever no painel -----

    #[Test]
    public function o_membro_colunista_escreve_e_o_texto_vai_para_analise(): void
    {
        [, $user, $perfil] = $this->membroColunista();

        $this->actingAs($user)->post('/membro/colunas', $this->textoValido())->assertRedirect('/membro/colunas');

        $coluna = Post::first();

        $this->assertSame($perfil->id, $coluna->columnist_id);
        $this->assertTrue($coluna->isColumn());
        $this->assertTrue($coluna->isPending());
        $this->assertFalse((bool) $coluna->is_published);
    }

    #[Test]
    public function a_empresa_colunista_tambem_escreve(): void
    {
        [, $user, $perfil] = $this->empresaColunista();

        $this->actingAs($user)->post('/empresa/colunas', $this->textoValido())->assertRedirect('/empresa/colunas');

        $this->assertSame($perfil->id, Post::first()->columnist_id);
    }

    #[Test]
    public function quem_nao_e_colunista_nao_escreve(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $company->id]);

        $this->actingAs($user)->get('/empresa/colunas')->assertForbidden();
        $this->actingAs($user)->post('/empresa/colunas', $this->textoValido())->assertForbidden();
    }

    #[Test]
    public function colunista_nao_alcanca_a_coluna_de_outro(): void
    {
        [, $user] = $this->membroColunista();

        $outro = Member::factory()->create(['name' => 'Bruno Lima']);
        $colunaAlheia = Post::factory()->create([
            'columnist_id' => $this->marcarComoColunista($outro)->id,
        ]);

        $this->actingAs($user)->get("/membro/colunas/{$colunaAlheia->id}/editar")->assertNotFound();
        $this->actingAs($user)->put("/membro/colunas/{$colunaAlheia->id}", $this->textoValido())->assertNotFound();
        $this->actingAs($user)->delete("/membro/colunas/{$colunaAlheia->id}")->assertNotFound();
    }

    #[Test]
    public function colunista_nao_alcanca_noticia_da_diretoria(): void
    {
        [, $user] = $this->membroColunista();

        // Noticia sem colunista: nao pode cair no escopo do painel.
        $noticia = Post::factory()->create(['columnist_id' => null]);

        $this->actingAs($user)->get("/membro/colunas/{$noticia->id}/editar")->assertNotFound();
    }

    #[Test]
    public function editar_uma_coluna_publicada_devolve_para_analise(): void
    {
        [, $user, $perfil] = $this->membroColunista();

        $coluna = Post::factory()->create([
            'columnist_id' => $perfil->id,
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->put("/membro/colunas/{$coluna->id}", $this->textoValido(['title' => 'Título revisado']))
            ->assertRedirect();

        $coluna->refresh();

        $this->assertSame('Título revisado', $coluna->title);
        $this->assertTrue($coluna->isPending());
        $this->assertFalse((bool) $coluna->is_published);
    }

    // ----- Moderação -----

    #[Test]
    public function a_diretoria_aprova_e_a_coluna_entra_no_portal(): void
    {
        [, , $perfil] = $this->membroColunista();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $coluna = Post::factory()->create([
            'columnist_id' => $perfil->id,
            'status' => \App\Enums\ModerationStatus::Pending,
            'is_published' => false,
            'published_at' => null,
        ]);

        $this->actingAs($admin)->patch("/admin/colunas/{$coluna->id}/aprovar")->assertRedirect();

        $coluna->refresh();

        $this->assertTrue($coluna->isApproved());
        $this->assertTrue((bool) $coluna->is_published);
        $this->assertNotNull($coluna->published_at);

        $this->get(route('posts.show', $coluna))->assertOk();
    }

    #[Test]
    public function a_devolucao_guarda_o_motivo_para_o_colunista(): void
    {
        [, $user, $perfil] = $this->membroColunista();
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $coluna = Post::factory()->create([
            'columnist_id' => $perfil->id,
            'status' => \App\Enums\ModerationStatus::Pending,
            'is_published' => false,
        ]);

        $this->actingAs($admin)
            ->patch("/admin/colunas/{$coluna->id}/rejeitar", ['rejection_reason' => 'Falta desenvolver o segundo argumento.'])
            ->assertRedirect();

        $this->assertTrue($coluna->fresh()->isRejected());

        // O colunista ve o motivo no painel dele.
        $this->actingAs($user)->get('/membro/colunas')
            ->assertOk()
            ->assertSee('Falta desenvolver o segundo argumento.');
    }

    #[Test]
    public function admin_sem_o_modulo_colunas_leva_403(): void
    {
        $restrito = User::factory()->create(['role' => UserRole::Admin, 'abilities' => ['members']]);

        $this->actingAs($restrito)->get('/admin/colunas')->assertForbidden();
        $this->actingAs($restrito)->get('/admin/colunas-pendentes')->assertForbidden();
        $this->actingAs($restrito)->get('/admin/colunistas')->assertForbidden();
    }

    // ----- Site público -----

    #[Test]
    public function coluna_em_analise_nao_aparece_no_portal(): void
    {
        [, , $perfil] = $this->membroColunista();

        $pendente = Post::factory()->create([
            'columnist_id' => $perfil->id,
            'title' => 'Ainda em análise',
            'status' => \App\Enums\ModerationStatus::Pending,
            'is_published' => false,
            'published_at' => null,
        ]);

        $this->get(route('colunas.index'))->assertOk()->assertDontSee($pendente->title);
        $this->get(route('posts.show', $pendente))->assertNotFound();
    }

    #[Test]
    public function a_pagina_do_colunista_lista_os_textos_dele(): void
    {
        [, , $perfil] = $this->membroColunista();

        $coluna = Post::factory()->create([
            'columnist_id' => $perfil->id,
            'title' => 'Coluna publicada da Ana',
        ]);

        $this->get(route('colunas.show', $perfil->slug))
            ->assertOk()
            ->assertSee('Ana Souza')
            ->assertSee($coluna->title);
    }

    #[Test]
    public function colunista_de_cadastro_fora_do_ar_nao_tem_pagina(): void
    {
        // Membro despublicado nao pode continuar assinando na vitrine.
        $member = Member::factory()->create(['is_active' => false]);
        $perfil = $this->marcarComoColunista($member);

        Post::factory()->create(['columnist_id' => $perfil->id]);

        $this->get(route('colunas.show', $perfil->slug))->assertNotFound();
        $this->get(route('colunas.index'))->assertOk()->assertDontSee($member->name);
    }

    #[Test]
    public function colunista_desativado_sai_da_vitrine(): void
    {
        [$member, , $perfil] = $this->membroColunista();
        Post::factory()->create(['columnist_id' => $perfil->id]);

        app(SyncColumnistProfile::class)($member, false);

        $this->get(route('colunas.show', $perfil->slug))->assertNotFound();
    }

    #[Test]
    public function a_coluna_aparece_assinada_e_marcada_como_coluna(): void
    {
        [, , $perfil] = $this->membroColunista();
        $coluna = Post::factory()->create(['columnist_id' => $perfil->id]);

        $this->get(route('posts.show', $coluna))
            ->assertOk()
            ->assertSee('Ana Souza')
            ->assertSee(route('colunas.show', $perfil->slug))
            // O aviso separa opiniao assinada de posicao da associacao.
            ->assertSee('não representam necessariamente a posição da associação', false);
    }

    #[Test]
    public function noticia_comum_nao_ganha_assinatura_nem_aviso(): void
    {
        $noticia = Post::factory()->create(['columnist_id' => null]);

        $this->get(route('posts.show', $noticia))
            ->assertOk()
            ->assertDontSee('não representam necessariamente a posição da associação', false);
    }

    #[Test]
    public function a_noticia_da_diretoria_continua_publicando_sem_analise(): void
    {
        // A moderacao entrou junto com as colunas; noticia da propria
        // diretoria nao pode ter passado a exigir aprovacao.
        $admin = User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);

        $this->actingAs($admin)->post(route('admin.cms.posts.store'), [
            'title' => 'Comunicado da diretoria',
            'body' => str_repeat('Conteúdo institucional da associação. ', 4),
            'category' => 'Notícias',
            'is_published' => '1',
        ])->assertRedirect();

        $noticia = Post::where('title', 'Comunicado da diretoria')->first();

        $this->assertTrue($noticia->isApproved());
        $this->get(route('posts.show', $noticia))->assertOk();
    }
}
