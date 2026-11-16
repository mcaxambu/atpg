<?php

namespace Tests\Feature;

use App\Actions\GerarMensalidades;
use App\Enums\MensalidadeStatus;
use App\Models\Company;
use App\Models\Mensalidade;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Retorno do Asaas.
 *
 * Isto dá baixa em dinheiro sem ninguém olhando, então o que os testes
 * guardam é: token errado não entra; o mesmo evento repetido não quebra nada;
 * e um evento que a gente não entende NÃO derruba a resposta — o Asaas pausa
 * a fila da conta inteira depois de 15 falhas seguidas.
 */
class AsaasWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'token-de-teste-123';

    private function mensalidade(): Mensalidade
    {
        SiteSetting::setValue(GerarMensalidades::CHAVE_VALOR, '150.00');
        SiteSetting::setValue(GerarMensalidades::CHAVE_DIA_VENCIMENTO, '10');

        Company::factory()->create();
        app(GerarMensalidades::class)(Carbon::parse('2026-11-01'));

        return Mensalidade::firstOrFail();
    }

    private function evento(Mensalidade $mensalidade, string $evento, array $extra = []): array
    {
        return [
            'event' => $evento,
            'payment' => $extra + [
                'id' => 'pay_123',
                'externalReference' => 'mensalidade-'.$mensalidade->id,
                'billingType' => 'PIX',
                'paymentDate' => '2026-11-08',
                'value' => 150.00,
            ],
        ];
    }

    private function enviar(array $payload, ?string $token = self::TOKEN)
    {
        config(['asaas.webhook_token' => self::TOKEN]);

        return $this->withHeaders($token ? ['asaas-access-token' => $token] : [])
            ->postJson(route('asaas.webhook'), $payload);
    }

    // ----- Porta de entrada -----

    #[Test]
    public function sem_o_token_certo_nao_entra(): void
    {
        $mensalidade = $this->mensalidade();

        $this->enviar($this->evento($mensalidade, 'PAYMENT_RECEIVED'), 'token-errado')
            ->assertStatus(401);

        $this->assertSame(MensalidadeStatus::Pendente, $mensalidade->fresh()->status);
    }

    #[Test]
    public function sem_token_configurado_o_endereco_fica_fechado(): void
    {
        // Aberto, qualquer um daria baixa em mensalidade dos outros.
        $mensalidade = $this->mensalidade();
        config(['asaas.webhook_token' => null]);

        $this->withHeaders(['asaas-access-token' => 'qualquer'])
            ->postJson(route('asaas.webhook'), $this->evento($mensalidade, 'PAYMENT_RECEIVED'))
            ->assertStatus(401);

        $this->assertSame(MensalidadeStatus::Pendente, $mensalidade->fresh()->status);
    }

    // ----- A baixa -----

    #[Test]
    public function pagamento_recebido_da_baixa(): void
    {
        $mensalidade = $this->mensalidade();

        $this->enviar($this->evento($mensalidade, 'PAYMENT_RECEIVED'))->assertOk();

        $mensalidade->refresh();

        $this->assertSame(MensalidadeStatus::Paga, $mensalidade->status);
        $this->assertSame('2026-11-08', $mensalidade->pago_em->format('Y-m-d'));
        $this->assertSame('pix', $mensalidade->forma_pagamento);
    }

    #[Test]
    public function o_mesmo_evento_repetido_nao_muda_nada(): void
    {
        // A entrega do Asaas é "pelo menos uma vez": repetição é o normal.
        $mensalidade = $this->mensalidade();

        $this->enviar($this->evento($mensalidade, 'PAYMENT_RECEIVED'))->assertOk();
        $primeiraBaixa = $mensalidade->fresh()->pago_em;

        $this->enviar($this->evento($mensalidade, 'PAYMENT_CONFIRMED', ['paymentDate' => '2026-11-20']))->assertOk();

        $this->assertSame(
            $primeiraBaixa->format('Y-m-d'),
            $mensalidade->fresh()->pago_em->format('Y-m-d')
        );
    }

    #[Test]
    public function estorno_reabre_a_mensalidade(): void
    {
        $mensalidade = $this->mensalidade();

        $this->enviar($this->evento($mensalidade, 'PAYMENT_RECEIVED'))->assertOk();
        $this->enviar($this->evento($mensalidade, 'PAYMENT_REFUNDED'))->assertOk();

        $mensalidade->refresh();

        $this->assertSame(MensalidadeStatus::Pendente, $mensalidade->status);
        $this->assertNull($mensalidade->pago_em);
    }

    // ----- O que não pode derrubar a fila -----

    #[Test]
    public function evento_sem_referencia_nossa_responde_ok(): void
    {
        // Pix pago direto na chave da conta chega aqui sem externalReference.
        $this->enviar(['event' => 'PAYMENT_RECEIVED', 'payment' => ['id' => 'pay_x']])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    #[Test]
    public function referencia_de_mensalidade_apagada_responde_ok(): void
    {
        $this->enviar([
            'event' => 'PAYMENT_RECEIVED',
            'payment' => ['id' => 'pay_x', 'externalReference' => 'mensalidade-99999'],
        ])->assertOk();
    }

    #[Test]
    public function evento_desconhecido_responde_ok_sem_mexer(): void
    {
        $mensalidade = $this->mensalidade();

        $this->enviar($this->evento($mensalidade, 'PAYMENT_UPDATED'))->assertOk();

        $this->assertSame(MensalidadeStatus::Pendente, $mensalidade->fresh()->status);
    }
}
