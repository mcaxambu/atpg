<?php

namespace App\Support\Radar;

use Anthropic\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Lê um achado do radar e devolve os campos preenchidos.
 *
 * O trabalho do modelo aqui é pequeno e bem delimitado de propósito: dizer se
 * o item é assunto da associação, de que tipo ele é, e extrair data, local e
 * organizador. Quem decide o que vai ao portal continua sendo a diretoria.
 *
 * A saída é presa a um esquema JSON: ou vem um objeto com exatamente estes
 * campos, ou o item é marcado como ilegível. Texto solto de modelo entrando
 * direto no banco é como se enche uma tabela de lixo que ninguém revisa.
 *
 * Modelo: Haiku 5.5, escolhido pelo usuário em 16/11/2026 — classificar uma
 * notícia curta e tirar dela uma data não precisa de mais do que isso, e a
 * conta do mês fica em alguns reais. Trocar está a uma linha do .env.
 */
class LeitorDeItem
{
    private const MODELO_PADRAO = 'claude-haiku-5-5';

    private const MAX_TOKENS = 2000;

    /** Texto da fonte entregue ao modelo; o resto é ruído de página. */
    private const LIMITE_DE_TEXTO = 6000;

    public function __construct(private readonly ?Client $client = null) {}

    /**
     * @param  array{fonte: string, url: string, titulo: string, resumo: ?string, publicado_em: ?Carbon}  $item
     * @return array<string, mixed>|null  null = não consegui ler
     */
    public function ler(array $item, ?string $textoDaPagina = null): ?array
    {
        $client = $this->client ?? new Client(apiKey: (string) config('services.anthropic.key'));

        $resposta = $client->messages->create(
            model: (string) config('services.anthropic.model', self::MODELO_PADRAO),
            maxTokens: self::MAX_TOKENS,
            system: [['type' => 'text', 'text' => $this->instrucoes()]],
            messages: [['role' => 'user', 'content' => $this->conteudo($item, $textoDaPagina)]],
            outputConfig: [
                'effort' => 'low',
                'format' => ['type' => 'json_schema', 'schema' => $this->esquema()],
            ],
        );

        if ($resposta->stopReason === 'refusal') {
            Log::warning('Radar: leitura recusada pelo modelo.', ['url' => $item['url']]);

            return null;
        }

        foreach ($resposta->content as $bloco) {
            if ($bloco->type !== 'text') {
                continue;
            }

            $dados = json_decode($bloco->text, true);

            // Resposta cortada no meio devolve JSON quebrado: tratar como
            // ilegível é melhor do que gravar campo pela metade.
            return is_array($dados) ? self::normalizar($dados) : null;
        }

        return null;
    }

    private function instrucoes(): string
    {
        return <<<'TEXTO'
        Você ajuda a Associação de Tecnologia de Ponta Grossa (ATPG) a triar o que vale
        publicar no portal dela.

        Interessa à associação o que for sobre TECNOLOGIA, INOVAÇÃO, STARTUPS, CIÊNCIA
        APLICADA ou EMPREENDEDORISMO DE BASE TECNOLÓGICA **e** tiver ligação com Ponta
        Grossa ou os Campos Gerais (ou ser um edital/oportunidade aberta a empresas e
        pesquisadores da região, mesmo que o órgão seja estadual ou federal).

        Classifique em:
        - "evento": acontece em data definida — palestra, feira, hackathon, summit, curso,
          semana acadêmica, meetup.
        - "edital": chamada pública, inscrição aberta, financiamento, prêmio, programa de
          aceleração, bolsa.
        - "noticia": o resto que interessa — e já aconteceu ou não tem data de acontecer.
        - "irrelevante": tudo o mais. Use sem medo. Política partidária, obra urbana,
          esporte, polícia, saúde sem recorte tecnológico, publicidade de loja, e também
          tecnologia sem nenhuma ligação com a região.

        Regras ao extrair:
        - Datas no formato AAAA-MM-DD. Hora no formato HH:MM, em 24h.
        - A data de publicação do texto está informada. Use-a para resolver expressões
          como "na próxima quinta" ou "neste sábado".
        - Se a data não estiver no texto, deixe null. NUNCA invente, chute ou aproxime
          uma data: a agenda da associação com data errada é pior do que sem o evento.
        - "titulo" e "resumo" têm de ser escritos POR VOCÊ, em português do Brasil, não
          copiados do texto da fonte. Resumo de 2 a 4 frases, direto, sem adjetivo de
          propaganda.
        - "confianca" é o quanto você confia na extração inteira, de 0 a 100. Abaixo de
          60 significa "alguém precisa conferir antes de usar".
        - "motivo" é uma frase curta dizendo por que isto interessa (ou por que não).
        TEXTO;
    }

    /**
     * @param  array{fonte: string, url: string, titulo: string, resumo: ?string, publicado_em: ?Carbon}  $item
     */
    private function conteudo(array $item, ?string $textoDaPagina): string
    {
        $publicado = $item['publicado_em']?->format('Y-m-d') ?? 'não informada';

        $texto = trim((string) ($textoDaPagina ?: $item['resumo'] ?: ''));

        return implode("\n\n", array_filter([
            'HOJE É '.now()->format('Y-m-d').'.',
            'FONTE: '.Fontes::nome($item['fonte']),
            'DATA DE PUBLICAÇÃO: '.$publicado,
            'ENDEREÇO: '.$item['url'],
            'TÍTULO NA FONTE: '.$item['titulo'],
            $texto !== '' ? "TEXTO:\n".Str::limit($texto, self::LIMITE_DE_TEXTO, '') : null,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function esquema(): array
    {
        $textoOuNulo = ['type' => ['string', 'null']];

        return [
            'type' => 'object',
            'properties' => [
                'tipo' => ['type' => 'string', 'enum' => ['evento', 'edital', 'noticia', 'irrelevante']],
                'titulo' => ['type' => 'string'],
                'resumo' => ['type' => 'string'],
                'data_inicio' => $textoOuNulo,
                'data_fim' => $textoOuNulo,
                'hora_inicio' => $textoOuNulo,
                'local' => $textoOuNulo,
                'organizador' => $textoOuNulo,
                'inscricao_url' => $textoOuNulo,
                'confianca' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 100],
                'motivo' => ['type' => 'string'],
            ],
            'required' => [
                'tipo', 'titulo', 'resumo', 'data_inicio', 'data_fim', 'hora_inicio',
                'local', 'organizador', 'inscricao_url', 'confianca', 'motivo',
            ],
            'additionalProperties' => false,
        ];
    }

    /**
     * Passa o que o modelo devolveu por uma peneira antes do banco.
     *
     * É aqui que mora o risco real desta integração: data em formato estranho,
     * tipo fora da lista, URL que não é URL. Nada disso pode virar linha no
     * banco — por isso é público e tem teste próprio, sem precisar da API.
     *
     * @param  array<string, mixed>  $dados
     * @return array<string, mixed>
     */
    public static function normalizar(array $dados): array
    {
        $data = function (mixed $valor): ?string {
            if (! is_string($valor) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
                return null;
            }

            try {
                return Carbon::createFromFormat('Y-m-d', $valor)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        };

        $texto = fn (mixed $valor, int $limite): ?string => is_string($valor) && trim($valor) !== ''
            ? Str::limit(trim($valor), $limite, '')
            : null;

        return [
            'tipo' => in_array($dados['tipo'] ?? null, ['evento', 'edital', 'noticia'], true)
                ? $dados['tipo']
                : 'irrelevante',
            'titulo' => $texto($dados['titulo'] ?? null, 250),
            'resumo' => $texto($dados['resumo'] ?? null, 2000),
            'data_inicio' => $data($dados['data_inicio'] ?? null),
            'data_fim' => $data($dados['data_fim'] ?? null),
            'hora_inicio' => is_string($dados['hora_inicio'] ?? null)
                && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $dados['hora_inicio'])
                    ? $dados['hora_inicio']
                    : null,
            'local' => $texto($dados['local'] ?? null, 250),
            'organizador' => $texto($dados['organizador'] ?? null, 250),
            'inscricao_url' => filter_var($dados['inscricao_url'] ?? null, FILTER_VALIDATE_URL) ?: null,
            'confianca' => max(0, min(100, (int) ($dados['confianca'] ?? 0))),
            'motivo' => $texto($dados['motivo'] ?? null, 500),
        ];
    }
}
