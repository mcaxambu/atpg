<?php

namespace App\Http\Controllers;

use App\Enums\MensalidadeStatus;
use App\Models\Mensalidade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Retorno do Asaas: dá baixa sozinho quando o associado paga.
 *
 * Três coisas que a Vox e o Gestor Político já ensinaram, e que estão aqui
 * de propósito:
 *
 * 1. **Responder 2xx quase sempre.** O Asaas pausa a fila da CONTA INTEIRA
 *    depois de 15 falhas seguidas — uma exceção nossa num evento qualquer
 *    deixaria de receber o retorno de todas as cobranças, caladamente. Só o
 *    token errado devolve erro.
 * 2. **A entrega é "pelo menos uma vez".** O mesmo pagamento chega repetido,
 *    então a baixa confere antes se já foi dada.
 * 3. **O webhook é por CONTA, não por cobrança.** Quem diz a qual mensalidade
 *    o evento pertence é o `externalReference` que mandamos na emissão.
 */
class AsaasWebhookController extends Controller
{
    /** Eventos que significam "o dinheiro entrou". */
    private const PAGOU = ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'];

    /** Pagamento desfeito depois de confirmado. */
    private const DESFEZ = ['PAYMENT_REFUNDED', 'PAYMENT_CHARGEBACK_REQUESTED', 'PAYMENT_DELETED'];

    public function __invoke(Request $request): JsonResponse
    {
        $token = (string) config('asaas.webhook_token');

        // Sem token configurado o endereço fica fechado: aberto, qualquer um
        // poderia dar baixa em mensalidade dos outros.
        if (blank($token) || ! hash_equals($token, (string) $request->header('asaas-access-token'))) {
            Log::warning('Webhook do Asaas recusado: token inválido.', ['ip' => $request->ip()]);

            return response()->json(['erro' => 'token invalido'], 401);
        }

        $evento = (string) $request->input('event');
        $pagamento = (array) $request->input('payment', []);
        $referencia = (string) ($pagamento['externalReference'] ?? '');

        if (! str_starts_with($referencia, 'mensalidade-')) {
            // Pix recebido direto na chave da conta chega aqui sem referência
            // nossa. Não é erro — é dinheiro que não passou pelo portal.
            return response()->json(['ok' => true, 'ignorado' => 'sem referencia']);
        }

        $mensalidade = Mensalidade::find((int) str_replace('mensalidade-', '', $referencia));

        if (! $mensalidade) {
            Log::warning('Webhook do Asaas para mensalidade inexistente.', ['referencia' => $referencia]);

            return response()->json(['ok' => true, 'ignorado' => 'mensalidade removida']);
        }

        try {
            $this->aplicar($mensalidade, $evento, $pagamento);
        } catch (\Throwable $erro) {
            // Ver o motivo 1 no topo da classe: engolir aqui é deliberado.
            Log::error('Webhook do Asaas falhou ao aplicar.', [
                'referencia' => $referencia,
                'evento' => $evento,
                'erro' => $erro->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @param  array<string, mixed>  $pagamento
     */
    private function aplicar(Mensalidade $mensalidade, string $evento, array $pagamento): void
    {
        if (in_array($evento, self::PAGOU, true)) {
            // Idempotência: o mesmo evento chega mais de uma vez.
            if ($mensalidade->status === MensalidadeStatus::Paga) {
                return;
            }

            $mensalidade->update([
                'status' => MensalidadeStatus::Paga,
                'pago_em' => isset($pagamento['paymentDate'])
                    ? Carbon::parse($pagamento['paymentDate'])
                    : Carbon::today(),
                'forma_pagamento' => $this->forma((string) ($pagamento['billingType'] ?? '')),
                'cobranca_externa_id' => $pagamento['id'] ?? $mensalidade->cobranca_externa_id,
                'observacao' => 'Baixa automática pelo Asaas.',
            ]);

            return;
        }

        if (in_array($evento, self::DESFEZ, true) && $mensalidade->status === MensalidadeStatus::Paga) {
            $mensalidade->update([
                'status' => MensalidadeStatus::Pendente,
                'pago_em' => null,
                'observacao' => 'Pagamento desfeito no Asaas ('.$evento.').',
            ]);
        }
    }

    private function forma(string $billingType): ?string
    {
        return match ($billingType) {
            'PIX' => 'pix',
            'BOLETO' => 'boleto',
            'CREDIT_CARD', 'DEBIT_CARD' => 'cartao',
            'TRANSFER' => 'transferencia',
            default => null,
        };
    }
}
