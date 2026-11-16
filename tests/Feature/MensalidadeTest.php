<?php

namespace Tests\Feature;

use App\Actions\GerarMensalidades;
use App\Enums\MensalidadeStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Mensalidade;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Mensalidade dos associados.
 *
 * Isto mexe com dinheiro de outras pessoas, então o que os testes guardam é,
 * em ordem: não cobrar duas vezes, não cobrar quem não deve, e a empresa não
 * poder dar baixa no próprio pagamento.
 */
class MensalidadeTest extends TestCase
{
    use RefreshDatabase;

    private function diretoria(): User
    {
        return User::factory()->create(['role' => UserRole::Admin, 'abilities' => null]);
    }

    private function configurar(string $valor = '150.00', int $dia = 10): void
    {
        SiteSetting::setValue(GerarMensalidades::CHAVE_VALOR, $valor);
        SiteSetting::setValue(GerarMensalidades::CHAVE_DIA_VENCIMENTO, (string) $dia);
    }

    // ----- Geração -----

    #[Test]
    public function gerar_duas_vezes_nao_cobra_duas_vezes(): void
    {
        // Clique repetido, cron que rodou duas vezes: acontece, e cobrar de
        // novo seria estrago com dinheiro dos outros.
        $this->configurar();
        Company::factory()->count(3)->create();

        $gerar = app(GerarMensalidades::class);

        $primeira = $gerar(Carbon::parse('2026-11-01'));
        $segunda = $gerar(Carbon::parse('2026-11-01'));

        $this->assertSame(3, $primeira['geradas']);
        $this->assertSame(0, $segunda['geradas']);
        $this->assertSame(3, $segunda['puladas']);
        $this->assertSame(3, Mensalidade::count());
    }

    #[Test]
    public function associada_isenta_nao_entra_na_geracao(): void
    {
        $this->configurar();
        Company::factory()->create(['name' => 'Paga']);
        Company::factory()->create(['name' => 'Isenta', 'mensalidade_isenta' => true]);

        app(GerarMensalidades::class)(Carbon::parse('2026-11-01'));

        $this->assertSame(1, Mensalidade::count());
        $this->assertSame('Paga', Mensalidade::first()->company->name);
    }

    #[Test]
    public function o_comando_recusa_rodar_sem_valor_configurado(): void
    {
        // Valor zero é configuração faltando, não mensalidade de graça: 27
        // cobranças de R$ 0,00 teriam de ser desfeitas uma a uma.
        Company::factory()->create();

        $this->artisan('atpg:mensalidades')
            ->expectsOutputToContain('não está configurado')
            ->assertFailed();

        $this->assertSame(0, Mensalidade::count());
    }

    #[Test]
    public function o_vencimento_usa_o_dia_configurado(): void
    {
        $this->configurar(dia: 15);
        Company::factory()->create();

        app(GerarMensalidades::class)(Carbon::parse('2026-11-01'));

        $this->assertSame('2026-11-15', Mensalidade::first()->vencimento->format('Y-m-d'));
    }

    // ----- Baixa -----

    #[Test]
    public function a_diretoria_da_baixa_e_consegue_desfazer(): void
    {
        $this->configurar();
        Company::factory()->create();
        app(GerarMensalidades::class)(Carbon::parse('2026-11-01'));

        $mensalidade = Mensalidade::firstOrFail();
        $admin = $this->diretoria();

        $this->actingAs($admin)
            ->post(route('admin.financeiro.pagar', $mensalidade), ['forma_pagamento' => 'pix'])
            ->assertRedirect();

        $mensalidade->refresh();
        $this->assertSame(MensalidadeStatus::Paga, $mensalidade->status);
        $this->assertNotNull($mensalidade->pago_em);

        // Erro de clique em dinheiro precisa de volta.
        $this->actingAs($admin)->post(route('admin.financeiro.reabrir', $mensalidade))->assertRedirect();

        $mensalidade->refresh();
        $this->assertSame(MensalidadeStatus::Pendente, $mensalidade->status);
        $this->assertNull($mensalidade->pago_em);
    }

    #[Test]
    public function a_empresa_nao_da_baixa_no_proprio_pagamento(): void
    {
        $this->configurar();
        $empresa = Company::factory()->create();
        app(GerarMensalidades::class)(Carbon::parse('2026-11-01'));

        $mensalidade = Mensalidade::firstOrFail();
        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $empresa->id]);

        $this->actingAs($user)
            ->post(route('admin.financeiro.pagar', $mensalidade), ['forma_pagamento' => 'pix'])
            ->assertRedirect(route('empresa.dashboard'));

        $this->assertSame(MensalidadeStatus::Pendente, $mensalidade->fresh()->status);
    }

    // ----- O que cada um vê -----

    #[Test]
    public function a_empresa_ve_so_as_proprias_mensalidades(): void
    {
        $this->configurar();
        $minha = Company::factory()->create(['name' => 'Minha Empresa']);
        Company::factory()->create(['name' => 'Empresa Alheia']);
        app(GerarMensalidades::class)(Carbon::parse('2026-11-01'));

        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $minha->id]);

        $this->actingAs($user)->get(route('empresa.mensalidades.index'))
            ->assertOk()
            ->assertDontSee('Empresa Alheia');
    }

    #[Test]
    public function o_recibo_so_sai_de_mensalidade_paga_e_da_propria_empresa(): void
    {
        $this->configurar();
        $minha = Company::factory()->create();
        $outra = Company::factory()->create();
        app(GerarMensalidades::class)(Carbon::parse('2026-11-01'));

        $user = User::factory()->create(['role' => UserRole::Company, 'company_id' => $minha->id]);

        $minhaMensalidade = Mensalidade::where('company_id', $minha->id)->firstOrFail();
        $daOutra = Mensalidade::where('company_id', $outra->id)->firstOrFail();

        // Em aberto não gera recibo.
        $this->actingAs($user)->get(route('empresa.mensalidades.recibo', $minhaMensalidade))->assertNotFound();

        $minhaMensalidade->update(['status' => MensalidadeStatus::Paga, 'pago_em' => Carbon::today()]);
        $daOutra->update(['status' => MensalidadeStatus::Paga, 'pago_em' => Carbon::today()]);

        $this->actingAs($user)->get(route('empresa.mensalidades.recibo', $minhaMensalidade))->assertOk();
        // Recibo de outra empresa, nem paga.
        $this->actingAs($user)->get(route('empresa.mensalidades.recibo', $daOutra))->assertNotFound();
    }

    // ----- Contas -----

    #[Test]
    public function vencida_e_leitura_do_calendario_e_nao_estado_guardado(): void
    {
        $this->configurar(dia: 10);
        Company::factory()->create();
        app(GerarMensalidades::class)(Carbon::parse('2026-11-01'));

        $mensalidade = Mensalidade::firstOrFail();

        Carbon::setTestNow('2026-11-09');
        $this->assertFalse($mensalidade->estaVencida());

        Carbon::setTestNow('2026-11-11');
        $this->assertTrue($mensalidade->estaVencida());

        // Paga não fica vencida nunca, mesmo com o vencimento no passado.
        $mensalidade->update(['status' => MensalidadeStatus::Paga]);
        $this->assertFalse($mensalidade->estaVencida());

        Carbon::setTestNow();
    }

    #[Test]
    public function isenta_e_cancelada_nao_entram_na_previsao_de_receita(): void
    {
        // Somá-las infla a previsão com dinheiro que nunca vai chegar.
        $this->assertTrue(MensalidadeStatus::Pendente->contaComoReceita());
        $this->assertTrue(MensalidadeStatus::Paga->contaComoReceita());
        $this->assertFalse(MensalidadeStatus::Isenta->contaComoReceita());
        $this->assertFalse(MensalidadeStatus::Cancelada->contaComoReceita());
    }

    #[Test]
    public function a_tela_da_diretoria_mostra_previsto_e_recebido(): void
    {
        $this->configurar('100.00');
        Company::factory()->count(2)->create();
        app(GerarMensalidades::class)(Carbon::parse('2026-11-01'));

        Mensalidade::first()->update(['status' => MensalidadeStatus::Paga, 'pago_em' => Carbon::today()]);

        $this->actingAs($this->diretoria())
            ->get(route('admin.financeiro.index', ['competencia' => '2026-11']))
            ->assertOk()
            ->assertSee('R$ 200,00')  // previsto
            ->assertSee('R$ 100,00'); // recebido
    }
}
