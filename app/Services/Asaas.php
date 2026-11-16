<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Mensalidade;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Conversa com o Asaas — clientes e cobranças da mensalidade.
 *
 * O molde veio do que já roda na Vox e no Frenesi; as armadilhas que estão
 * comentadas aqui foram pagas lá:
 *
 * - chave de sandbox usada em produção (e vice-versa) é recusada ANTES de
 *   sair a requisição: a do sandbox falharia com "chave inválida", mas a de
 *   produção num teste cobraria de verdade;
 * - antes de criar, procura pelo `externalReference`: a conexão cai depois do
 *   Asaas gravar, nosso lado não guarda o id e a próxima tentativa criaria
 *   cliente e cobrança duplicados — cobrar duas vezes é estrago com dinheiro
 *   dos outros;
 * - associada sem CNPJ é recusada pelo Asaas. Isso é dito na tela antes de
 *   enviar, e não descoberto no meio do lote.
 */
class Asaas
{
    public function habilitado(): bool
    {
        return (bool) config('asaas.habilitado') && filled(config('asaas.api_key'));
    }

    public function ambiente(): string
    {
        return (string) config('asaas.ambiente');
    }

    /**
     * Garante o cliente no Asaas e devolve o id dele.
     */
    public function clienteDe(Company $empresa): string
    {
        if (filled($empresa->asaas_customer_id)) {
            return $empresa->asaas_customer_id;
        }

        $documento = preg_replace('/\D/', '', (string) $empresa->cnpj);

        if (strlen($documento) !== 14 && strlen($documento) !== 11) {
            throw new RuntimeException("A empresa \"{$empresa->name}\" está sem CNPJ válido — o Asaas recusa a cobrança.");
        }

        $referencia = 'empresa-'.$empresa->id;

        // Procura antes de criar: ver o comentário da classe.
        $existente = $this->requisicao('GET', '/customers', ['externalReference' => $referencia])['data'][0] ?? null;

        $dados = $existente ?: $this->requisicao('POST', '/customers', [
            'name' => $empresa->legal_name ?: $empresa->name,
            'cpfCnpj' => $documento,
            'email' => $empresa->email,
            'mobilePhone' => preg_replace('/\D/', '', (string) $empresa->whatsapp) ?: null,
            'externalReference' => $referencia,
            'notificationDisabled' => false,
        ]);

        $empresa->forceFill(['asaas_customer_id' => $dados['id']])->save();

        return $dados['id'];
    }

    /**
     * Emite (ou reaproveita) a cobrança de uma mensalidade.
     *
     * @return array{id: string, url: string}
     */
    public function cobrar(Mensalidade $mensalidade): array
    {
        $empresa = $mensalidade->company;

        if (! $empresa) {
            throw new RuntimeException('Mensalidade sem empresa.');
        }

        $cliente = $this->clienteDe($empresa);
        $referencia = 'mensalidade-'.$mensalidade->id;

        $existente = $this->requisicao('GET', '/payments', ['externalReference' => $referencia])['data'][0] ?? null;

        $dados = $existente ?: $this->requisicao('POST', '/payments', [
            'customer' => $cliente,
            'billingType' => config('asaas.forma', 'UNDEFINED'),
            'value' => (float) $mensalidade->valor,
            'dueDate' => $mensalidade->vencimento->format('Y-m-d'),
            'description' => 'Mensalidade '.$mensalidade->competenciaLabel().' — Associação Tech PG',
            'externalReference' => $referencia,
        ]);

        return [
            'id' => (string) $dados['id'],
            'url' => (string) ($dados['invoiceUrl'] ?? $dados['bankSlipUrl'] ?? ''),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function requisicao(string $metodo, string $caminho, array $payload = []): array
    {
        $resposta = $metodo === 'GET'
            ? $this->cliente()->get($caminho, $payload)
            : $this->cliente()->send($metodo, $caminho, ['json' => $payload]);

        if ($resposta->failed()) {
            $erro = $resposta->json('errors.0.description') ?? $resposta->body();

            Log::warning('Asaas recusou a chamada.', [
                'caminho' => $caminho,
                'status' => $resposta->status(),
                'erro' => mb_substr((string) $erro, 0, 300),
            ]);

            throw new RuntimeException('Asaas: '.mb_substr((string) $erro, 0, 200));
        }

        return (array) $resposta->json();
    }

    private function cliente(): PendingRequest
    {
        $ambiente = $this->ambiente();
        $base = config("asaas.base_url.$ambiente");

        if (! in_array($ambiente, ['sandbox', 'production'], true) || blank($base)) {
            throw new RuntimeException('ASAAS_AMBIENTE precisa ser "sandbox" ou "production".');
        }

        $chave = (string) config('asaas.api_key');

        if (blank($chave)) {
            throw new RuntimeException('ASAAS_API_KEY não está configurada.');
        }

        // As chaves dizem de onde são: `_prod_` e `_hmlg_`.
        if (($ambiente === 'sandbox' && str_contains($chave, '_prod_'))
            || ($ambiente === 'production' && str_contains($chave, '_hmlg_'))) {
            throw new RuntimeException("A chave do Asaas não é do ambiente {$ambiente}. Confira ASAAS_AMBIENTE e ASAAS_API_KEY.");
        }

        return Http::baseUrl($base)
            ->timeout((int) config('asaas.timeout', 20))
            ->acceptJson()
            ->withHeaders(['access_token' => $chave]);
    }
}
