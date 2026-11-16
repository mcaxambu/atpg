<?php

namespace Tests\Feature;

use App\Enums\RadarStatus;
use App\Enums\RadarTipo;
use App\Enums\UserRole;
use App\Models\Event;
use App\Models\Post;
use App\Models\RadarItem;
use App\Models\User;
use App\Support\Radar\LeitorDeItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Radar: varredura automática de eventos, notícias e editais da região.
 *
 * O que estes testes protegem, em ordem de importância:
 *
 * 1. nada vai ao portal sem decisão de gente — o rascunho nasce despublicado;
 * 2. o que o modelo devolve passa por peneira antes do banco (data inventada
 *    em formato estranho é o erro mais caro aqui: agenda com data errada);
 * 3. link já visto não volta para a fila amanhã.
 */
class RadarTest extends TestCase
{
    use RefreshDatabase;

    private function diretoria(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
    }

    private function achado(array $campos = []): RadarItem
    {
        return RadarItem::create($campos + [
            'fonte' => 'acipg',
            'url' => 'https://acipg.org.br/noticia-'.uniqid(),
            'url_hash' => RadarItem::hashDa('https://acipg.org.br/noticia-'.uniqid()),
            'titulo_original' => 'Startup Weekend movimenta Ponta Grossa',
            'tipo' => RadarTipo::Evento,
            'titulo' => 'Startup Weekend Ponta Grossa',
            'resumo' => 'Maratona de empreendedorismo com mentorias e apresentação de projetos.',
            'data_inicio' => now()->addMonth()->format('Y-m-d'),
            'local' => 'Estação Hub',
            'confianca' => 90,
            'status' => RadarStatus::Novo,
        ]);
    }

    // ----- Nada publica sozinho -----

    #[Test]
    public function o_rascunho_de_evento_nasce_despublicado(): void
    {
        $item = $this->achado();

        $this->actingAs($this->diretoria())
            ->post(route('admin.radar.publicar', $item))
            ->assertRedirect();

        $evento = Event::firstOrFail();

        $this->assertFalse((bool) $evento->is_published);
        $this->assertSame('Startup Weekend Ponta Grossa', $evento->title);
        $this->assertSame('Estação Hub', $evento->location);
    }

    #[Test]
    public function o_rascunho_leva_o_link_da_fonte_junto(): void
    {
        // Quem revisa precisa conferir contra o original, e o crédito é de
        // quem publicou primeiro.
        $item = $this->achado(['tipo' => RadarTipo::Noticia]);

        $this->actingAs($this->diretoria())->post(route('admin.radar.publicar', $item));

        $noticia = Post::firstOrFail();

        $this->assertFalse((bool) $noticia->is_published);
        $this->assertSame($item->url, $noticia->source_url);
        $this->assertSame('ACIPG', $noticia->source_name);
    }

    #[Test]
    public function publicar_marca_o_item_e_guarda_o_que_foi_criado(): void
    {
        $item = $this->achado();

        $this->actingAs($this->diretoria())->post(route('admin.radar.publicar', $item));

        $item->refresh();

        $this->assertSame(RadarStatus::Publicado, $item->status);
        $this->assertSame(Event::class, $item->publicado_type);
        $this->assertSame(Event::firstOrFail()->id, $item->publicado_id);
        $this->assertNotNull($item->decidido_em);
    }

    #[Test]
    public function item_fora_do_assunto_nao_vira_rascunho(): void
    {
        $item = $this->achado(['tipo' => RadarTipo::Irrelevante]);

        $this->actingAs($this->diretoria())
            ->post(route('admin.radar.publicar', $item))
            ->assertNotFound();

        $this->assertSame(0, Event::count());
    }

    #[Test]
    public function descartar_e_devolver_a_fila_funcionam(): void
    {
        $item = $this->achado();
        $admin = $this->diretoria();

        $this->actingAs($admin)->post(route('admin.radar.descartar', $item))->assertRedirect();
        $this->assertSame(RadarStatus::Descartado, $item->fresh()->status);

        // Engano de clique precisa de volta.
        $this->actingAs($admin)->post(route('admin.radar.restaurar', $item))->assertRedirect();
        $this->assertSame(RadarStatus::Novo, $item->fresh()->status);
    }

    #[Test]
    public function a_empresa_nao_entra_no_radar(): void
    {
        $empresa = User::factory()->create(['role' => UserRole::Company]);

        $this->actingAs($empresa)->get(route('admin.radar.index'))->assertRedirect();
    }

    // ----- A chave da API -----

    #[Test]
    public function chave_com_cara_de_exemplo_nao_e_usada(): void
    {
        /*
         * Aconteceu de verdade em 16/11: o texto de exemplo de uma instrução
         * foi colado dentro do .env. Sem esta conferência a varredura gastava
         * uma chamada recusada por item, toda madrugada, e a tela não dizia
         * nada — só enchia de "não consegui ler".
         */
        config(['services.anthropic.key' => 'sua-chave-aqui']);

        $this->artisan('atpg:radar', ['--fonte' => 'acipg', '--limite' => 1])
            ->expectsOutputToContain('não parece uma chave')
            ->assertSuccessful();
    }

    #[Test]
    public function sem_chave_o_radar_coleta_mas_nao_classifica(): void
    {
        config(['services.anthropic.key' => null]);

        $this->artisan('atpg:radar', ['--fonte' => 'acipg', '--limite' => 1])
            ->expectsOutputToContain('não configurada')
            ->assertSuccessful();
    }

    // ----- A peneira do que o modelo devolve -----

    #[Test]
    public function data_em_formato_estranho_vira_nulo_em_vez_de_data_errada(): void
    {
        $campos = LeitorDeItem::normalizar([
            'tipo' => 'evento',
            'titulo' => 'Evento',
            'resumo' => 'Resumo',
            'data_inicio' => '17 de maio',
            'data_fim' => 'em breve',
            'hora_inicio' => 'de manhã',
            'local' => null,
            'organizador' => null,
            'inscricao_url' => 'não informado',
            'confianca' => 80,
            'motivo' => 'é da região',
        ]);

        $this->assertNull($campos['data_inicio']);
        $this->assertNull($campos['data_fim']);
        $this->assertNull($campos['hora_inicio']);
        $this->assertNull($campos['inscricao_url']);
    }

    #[Test]
    public function tipo_fora_da_lista_cai_em_irrelevante(): void
    {
        $campos = LeitorDeItem::normalizar([
            'tipo' => 'palestra',
            'titulo' => 'Algo',
            'resumo' => 'Algo',
            'confianca' => 50,
            'motivo' => '-',
        ]);

        $this->assertSame('irrelevante', $campos['tipo']);
    }

    #[Test]
    public function a_confianca_fica_presa_entre_zero_e_cem(): void
    {
        $this->assertSame(100, LeitorDeItem::normalizar(['tipo' => 'noticia', 'confianca' => 900])['confianca']);
        $this->assertSame(0, LeitorDeItem::normalizar(['tipo' => 'noticia', 'confianca' => -5])['confianca']);
    }

    #[Test]
    public function data_valida_passa_inteira(): void
    {
        $campos = LeitorDeItem::normalizar([
            'tipo' => 'evento',
            'titulo' => 'Quantum Week',
            'resumo' => 'Semana de tecnologias quânticas.',
            'data_inicio' => '2027-05-17',
            'data_fim' => '2027-05-21',
            'hora_inicio' => '09:00',
            'inscricao_url' => 'https://exemplo.com.br/inscricao',
            'confianca' => 95,
            'motivo' => 'evento da região',
        ]);

        $this->assertSame('2027-05-17', $campos['data_inicio']);
        $this->assertSame('2027-05-21', $campos['data_fim']);
        $this->assertSame('09:00', $campos['hora_inicio']);
        $this->assertSame('https://exemplo.com.br/inscricao', $campos['inscricao_url']);
    }

    // ----- A fila -----

    #[Test]
    public function o_mesmo_link_nao_volta_para_a_fila(): void
    {
        $url = 'https://acipg.org.br/materia-unica';

        RadarItem::create([
            'fonte' => 'acipg',
            'url' => $url,
            'url_hash' => RadarItem::hashDa($url),
            'status' => RadarStatus::Descartado,
        ]);

        // A barra final não cria um item novo: é o mesmo endereço.
        $this->assertSame(RadarItem::hashDa($url), RadarItem::hashDa($url.'/'));
    }

    #[Test]
    public function a_tela_mostra_o_que_espera_decisao(): void
    {
        $this->achado();
        $this->achado(['status' => RadarStatus::Descartado, 'titulo' => 'Item descartado']);

        $this->actingAs($this->diretoria())->get(route('admin.radar.index'))
            ->assertOk()
            ->assertSee('Startup Weekend Ponta Grossa')
            ->assertDontSee('Item descartado');
    }

    #[Test]
    public function a_tela_avisa_quando_a_confianca_e_baixa(): void
    {
        // Esconder o item seria pior: ele some sem ninguém decidir nada.
        $this->achado(['confianca' => 35]);

        $this->actingAs($this->diretoria())->get(route('admin.radar.index'))
            ->assertOk()
            ->assertSee('confira com atenção');
    }
}
