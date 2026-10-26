<?php

namespace Tests\Feature;

use App\Enums\AdminModule;
use App\Enums\UserRole;
use App\Mail\ForumReplyMail;
use App\Models\Company;
use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Fórum dos associados.
 *
 * Duas coisas definem este fórum e estão presas aqui: ele é FECHADO (nada
 * aparece no site público nem para quem não entrou) e quem escreve assina com
 * a identidade do cadastro, não com um apelido.
 */
class ForumTest extends TestCase
{
    use RefreshDatabase;

    private function empresaLogada(string $nomeDaEmpresa = 'Dataholds'): User
    {
        $company = Company::factory()->create(['name' => $nomeDaEmpresa]);

        return User::factory()->create([
            'role' => UserRole::Company,
            'company_id' => $company->id,
            'name' => 'Rodrigo Rezende',
        ]);
    }

    private function membroLogado(): User
    {
        $member = Member::factory()->create(['name' => 'Ana Souza']);

        return User::factory()->create(['role' => UserRole::Member, 'member_id' => $member->id]);
    }

    private function diretoria(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
    }

    // ----- O fórum é fechado -----

    #[Test]
    public function visitante_sem_sessao_nao_alcanca_o_forum(): void
    {
        ForumTopic::factory()->create(['title' => 'Assunto interno da associação']);

        $this->get('/forum')->assertRedirect();
        $this->get('/forum')->assertRedirect();
    }

    #[Test]
    public function o_topico_nao_aparece_no_site_publico(): void
    {
        $topico = ForumTopic::factory()->create(['title' => 'Assunto interno da associação']);

        /*
            O fórum exige sessão: quem não entrou vai para o login e não lê
            nada. O endereço existir não é problema — o que não pode é o
            conteúdo aparecer para quem não é associado.
        */
        $this->get(route('home'))->assertOk()->assertDontSee($topico->title);

        $this->get('/forum')->assertRedirect()->assertDontSee($topico->title);
        $this->get('/forum/'.$topico->slug)->assertRedirect()->assertDontSee($topico->title);

        // E o portal público não ganhou rota de fórum por engano.
        $this->get('/forum-publico')->assertNotFound();
    }

    #[Test]
    public function topico_oculto_some_para_o_associado(): void
    {
        $user = $this->empresaLogada();
        $oculto = ForumTopic::factory()->oculto()->create(['title' => 'Tópico retirado do ar']);
        ForumTopic::factory()->create(['title' => 'Tópico normal']);

        $this->actingAs($user)->get(route('forum.index'))
            ->assertOk()
            ->assertSee('Tópico normal')
            ->assertDontSee('Tópico retirado do ar');

        $this->actingAs($user)->get(route('forum.show', $oculto->slug))->assertNotFound();
    }

    // ----- Escrever -----

    #[Test]
    public function a_empresa_abre_topico_e_assina_com_o_cadastro(): void
    {
        $user = $this->empresaLogada();
        $categoria = ForumCategory::factory()->create();

        $this->actingAs($user)->post(route('forum.store'), [
            'forum_category_id' => $categoria->id,
            'title' => 'Alguém já emitiu nota de serviço em PG?',
            'body' => '<p>Queria saber como vocês fazem.</p>',
        ])->assertRedirect();

        $topico = ForumTopic::firstOrFail();

        $this->assertSame($user->id, $topico->user_id);
        $this->assertNotEmpty($topico->slug);

        // A assinatura vem do cadastro: nome da pessoa e da empresa.
        $this->assertSame('Rodrigo Rezende — Dataholds', $user->assinaturaNoForum());

        $this->actingAs($user)->get(route('forum.show', $topico->slug))
            ->assertOk()
            ->assertSee('Rodrigo Rezende — Dataholds');
    }

    #[Test]
    public function o_membro_tambem_abre_topico(): void
    {
        $user = $this->membroLogado();
        $categoria = ForumCategory::factory()->create();

        $this->actingAs($user)->post(route('forum.store'), [
            'forum_category_id' => $categoria->id,
            'title' => 'Indicação de contador',
            'body' => '<p>Alguém indica?</p>',
        ])->assertRedirect();

        $this->assertSame(1, ForumTopic::count());
    }

    #[Test]
    public function topico_exige_assunto_titulo_e_texto(): void
    {
        $this->actingAs($this->empresaLogada())
            ->post(route('forum.store'), [])
            ->assertSessionHasErrors(['forum_category_id', 'title', 'body']);

        $this->assertSame(0, ForumTopic::count());
    }

    #[Test]
    public function o_html_do_topico_e_limpo_na_gravacao(): void
    {
        // O fórum é escrito por dezenas de pessoas, não só pela diretoria:
        // a lista de permissão do HTML importa ainda mais aqui.
        $user = $this->empresaLogada();
        $categoria = ForumCategory::factory()->create();

        $this->actingAs($user)->post(route('forum.store'), [
            'forum_category_id' => $categoria->id,
            'title' => 'Teste',
            'body' => '<p>Texto</p><script>alert(1)</script>',
        ]);

        $this->assertStringNotContainsString('script', ForumTopic::firstOrFail()->body);
    }

    // ----- Responder e avisar -----

    #[Test]
    public function responder_atualiza_a_contagem_e_avisa_quem_participa(): void
    {
        Mail::fake();

        $autor = $this->empresaLogada('Empresa do Autor');
        $autor->update(['email' => 'autor@empresa.test']);
        $quemResponde = $this->membroLogado();

        $topico = ForumTopic::factory()->create(['user_id' => $autor->id]);

        $this->actingAs($quemResponde)->post(route('forum.reply', $topico->slug), [
            'body' => '<p>Nós fazemos assim.</p>',
        ])->assertRedirect();

        $topico->refresh();

        $this->assertSame(1, $topico->replies_count);
        $this->assertNotNull($topico->last_reply_at);

        Mail::assertSent(ForumReplyMail::class, fn ($mail) => $mail->hasTo('autor@empresa.test'));
    }

    #[Test]
    public function quem_escreveu_a_resposta_nao_recebe_aviso_de_si_mesmo(): void
    {
        Mail::fake();

        $autor = $this->empresaLogada();
        $autor->update(['email' => 'autor@empresa.test']);

        $topico = ForumTopic::factory()->create(['user_id' => $autor->id]);

        $this->actingAs($autor)->post(route('forum.reply', $topico->slug), ['body' => '<p>Eu mesmo respondo.</p>']);

        Mail::assertNothingSent();
    }

    #[Test]
    public function topico_fechado_nao_aceita_resposta(): void
    {
        $user = $this->empresaLogada();
        $topico = ForumTopic::factory()->fechado()->create();

        $this->actingAs($user)
            ->post(route('forum.reply', $topico->slug), ['body' => '<p>Ainda dá?</p>'])
            ->assertForbidden();

        $this->assertSame(0, $topico->replies()->count());
    }

    // ----- Editar o próprio texto -----

    #[Test]
    public function o_autor_edita_o_proprio_topico_e_mais_ninguem(): void
    {
        $autor = $this->empresaLogada();
        $outro = $this->membroLogado();
        $topico = ForumTopic::factory()->create(['user_id' => $autor->id]);

        $this->actingAs($outro)->get(route('forum.edit', $topico->slug))->assertForbidden();

        $this->actingAs($autor)->put(route('forum.update', $topico->slug), [
            'forum_category_id' => $topico->forum_category_id,
            'title' => 'Título corrigido',
            'body' => '<p>Texto corrigido.</p>',
        ])->assertRedirect();

        $this->assertSame('Título corrigido', $topico->fresh()->title);
    }

    // ----- Moderação -----

    #[Test]
    public function a_diretoria_fixa_fecha_e_oculta(): void
    {
        $admin = $this->diretoria();
        $topico = ForumTopic::factory()->create();

        foreach (['fixar' => 'is_pinned', 'fechar' => 'is_closed', 'ocultar' => 'is_hidden'] as $acao => $campo) {
            $this->actingAs($admin)
                ->patch(route('admin.forum.topics.toggle', $topico), ['acao' => $acao])
                ->assertRedirect();

            $this->assertTrue((bool) $topico->fresh()->{$campo}, "esperava {$campo} ligado");
        }

        // Tudo reversível: a mesma ação desfaz.
        $this->actingAs($admin)->patch(route('admin.forum.topics.toggle', $topico), ['acao' => 'ocultar']);
        $this->assertFalse((bool) $topico->fresh()->is_hidden);
    }

    #[Test]
    public function associado_nao_modera(): void
    {
        $topico = ForumTopic::factory()->create();

        $this->actingAs($this->empresaLogada())
            ->patch(route('admin.forum.topics.toggle', $topico), ['acao' => 'ocultar'])
            ->assertRedirect();

        $this->assertFalse((bool) $topico->fresh()->is_hidden);
    }

    #[Test]
    public function admin_sem_o_modulo_nao_alcanca_a_moderacao(): void
    {
        $semForum = User::factory()->create([
            'role' => UserRole::Admin,
            'abilities' => [AdminModule::Members->value],
        ]);

        $this->actingAs($semForum)->get(route('admin.forum.topics.index'))->assertForbidden();
        $this->actingAs($semForum)->get(route('admin.forum.categories.index'))->assertForbidden();
    }

    #[Test]
    public function assunto_com_topico_nao_e_excluido(): void
    {
        // A conversa dos associados não é da diretoria para descartar.
        $categoria = ForumCategory::factory()->create();
        ForumTopic::factory()->create(['forum_category_id' => $categoria->id]);

        $this->actingAs($this->diretoria())
            ->delete(route('admin.forum.categories.destroy', $categoria))
            ->assertSessionHasErrors('categoria');

        $this->assertDatabaseHas('forum_categories', ['id' => $categoria->id]);
    }

    #[Test]
    public function assunto_desativado_some_de_quem_vai_abrir_topico(): void
    {
        $user = $this->empresaLogada();
        ForumCategory::factory()->create(['name' => 'Assunto Ativo']);
        ForumCategory::factory()->inativa()->create(['name' => 'Assunto Encerrado']);

        $this->actingAs($user)->get(route('forum.create'))
            ->assertOk()
            ->assertSee('Assunto Ativo')
            ->assertDontSee('Assunto Encerrado');
    }

    // ----- Lista -----

    #[Test]
    public function fixado_vem_antes_e_a_lista_segue_a_conversa_mais_recente(): void
    {
        $user = $this->empresaLogada();

        ForumTopic::factory()->create(['title' => 'Topico Antigo', 'last_reply_at' => now()->subWeek()]);
        ForumTopic::factory()->create(['title' => 'Topico Recente', 'last_reply_at' => now()]);
        ForumTopic::factory()->fixado()->create(['title' => 'Topico Fixado', 'last_reply_at' => now()->subMonth()]);

        $this->actingAs($user)->get(route('forum.index'))
            ->assertOk()
            ->assertSeeInOrder(['Topico Fixado', 'Topico Recente', 'Topico Antigo']);
    }

    // ----- A diretoria também é associada -----

    #[Test]
    public function a_diretoria_participa_do_forum_pelo_mesmo_endereco(): void
    {
        // O fórum nasceu com rota por painel e a diretoria ficava de fora:
        // moderava, mas não conversava.
        $admin = $this->diretoria();
        $categoria = ForumCategory::factory()->create();

        $this->actingAs($admin)->get(route('forum.index'))->assertOk();

        $this->actingAs($admin)->post(route('forum.store'), [
            'forum_category_id' => $categoria->id,
            'title' => 'Aviso da diretoria',
            'body' => '<p>Assembleia em novembro.</p>',
        ])->assertRedirect();

        $this->assertSame($admin->id, ForumTopic::firstOrFail()->user_id);
    }

    #[Test]
    public function a_diretoria_ve_o_forum_no_proprio_painel(): void
    {
        $this->actingAs($this->diretoria())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Participar do fórum');
    }

    #[Test]
    public function admin_sem_o_modulo_de_moderacao_ainda_participa(): void
    {
        // Participar e moderar são coisas diferentes: quem não modera continua
        // sendo associado e conversando.
        $semModulo = User::factory()->create([
            'role' => UserRole::Admin,
            'abilities' => [AdminModule::Members->value],
        ]);

        $this->actingAs($semModulo)->get(route('forum.index'))->assertOk();
        $this->actingAs($semModulo)->get(route('admin.forum.topics.index'))->assertForbidden();
    }

    #[Test]
    public function o_menu_do_associado_leva_ao_forum(): void
    {
        $this->actingAs($this->empresaLogada())->get(route('empresa.dashboard'))->assertOk()->assertSee('Fórum');
        $this->actingAs($this->membroLogado())->get(route('membro.perfil.edit'))->assertOk()->assertSee('Fórum');
    }
}
