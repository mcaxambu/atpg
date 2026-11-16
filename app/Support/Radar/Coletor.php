<?php

namespace App\Support\Radar;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Busca a lista de candidatos de cada fonte.
 *
 * Só monta a lista — não decide nada. Quem diz se o item interessa é a leitura
 * automatica, depois, e quem decide se publica é a diretoria, no fim.
 *
 * O agente se identifica no User-Agent com o endereço do portal: um robô que
 * se esconde é um robô que ninguém consegue bloquear nem procurar quando
 * incomoda, e essas fontes são de vizinhos nossos.
 */
class Coletor
{
    public const AGENTE = 'ATPG-Radar/1.0 (+https://atpg.net.br; radar de eventos de tecnologia)';

    private const TIMEOUT = 20;

    /** Teto por fonte, por varredura: um feed com problema não vira enxurrada. */
    private const MAXIMO_POR_FONTE = 20;

    /**
     * @return array<int, array{fonte: string, url: string, titulo: string, resumo: ?string, publicado_em: ?Carbon}>
     */
    public function coletar(string $slug): array
    {
        $fonte = Fontes::todas()[$slug] ?? null;

        if (! $fonte) {
            return [];
        }

        try {
            $corpo = $this->baixar($fonte['url']);
        } catch (\Throwable $erro) {
            Log::warning('Radar: fonte não respondeu.', ['fonte' => $slug, 'erro' => $erro->getMessage()]);

            return [];
        }

        if ($corpo === null) {
            return [];
        }

        $itens = $fonte['tipo'] === 'rss'
            ? $this->lerRss($corpo, $slug)
            : $this->lerListagemHtml($corpo, $slug, $fonte['host']);

        return array_slice($itens, 0, self::MAXIMO_POR_FONTE);
    }

    private function baixar(string $url): ?string
    {
        $resposta = Http::withHeaders(['User-Agent' => self::AGENTE])
            ->timeout(self::TIMEOUT)
            ->retry(2, 2000, throw: false)
            ->get($url);

        return $resposta->successful() ? $resposta->body() : null;
    }

    /**
     * @return array<int, array{fonte: string, url: string, titulo: string, resumo: ?string, publicado_em: ?Carbon}>
     */
    private function lerRss(string $xml, string $slug): array
    {
        $anterior = libxml_use_internal_errors(true);

        // LIBXML_NONET: um feed nao pode mandar o servidor buscar entidade
        // externa. Sem isso, XML de terceiro vira leitura de arquivo nosso.
        $rss = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET);

        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        if ($rss === false) {
            Log::warning('Radar: feed inválido.', ['fonte' => $slug]);

            return [];
        }

        $entradas = $rss->channel->item ?? $rss->entry ?? [];
        $itens = [];

        foreach ($entradas as $entrada) {
            $url = trim((string) ($entrada->link['href'] ?? $entrada->link ?? ''));
            $titulo = trim(html_entity_decode((string) $entrada->title, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

            if ($url === '' || $titulo === '') {
                continue;
            }

            $resumo = trim(strip_tags(html_entity_decode(
                (string) ($entrada->description ?? $entrada->summary ?? ''),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8'
            )));

            $itens[] = [
                'fonte' => $slug,
                'url' => $url,
                'titulo' => Str::limit($titulo, 250, ''),
                'resumo' => $resumo !== '' ? Str::limit($resumo, 1200, '') : null,
                'publicado_em' => $this->data((string) ($entrada->pubDate ?? $entrada->updated ?? '')),
            ];
        }

        return $itens;
    }

    /**
     * Listagem em HTML: pega os links da página e o texto do link.
     *
     * Não tenta entender o layout do site — layout muda e quebra calado. Aqui
     * interessa só juntar endereços plausíveis; o conteúdo de verdade vem
     * depois, da própria página do item.
     *
     * @return array<int, array{fonte: string, url: string, titulo: string, resumo: ?string, publicado_em: ?Carbon}>
     */
    private function lerListagemHtml(string $html, string $slug, string $host): array
    {
        $anterior = libxml_use_internal_errors(true);

        $doc = new \DOMDocument;
        $doc->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);

        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $itens = [];
        $vistos = [];

        foreach ($doc->getElementsByTagName('a') as $link) {
            $href = trim((string) $link->getAttribute('href'));
            $texto = trim(preg_replace('/\s+/u', ' ', (string) $link->textContent));

            if ($href === '' || mb_strlen($texto) < 15) {
                continue;
            }

            $absoluta = $this->absolutizar($href, $host);

            if ($absoluta === null || isset($vistos[$absoluta])) {
                continue;
            }

            // Só links da própria casa: menu de rede social e anúncio ficam de
            // fora sem precisar de lista de exceção.
            if (! str_contains(parse_url($absoluta, PHP_URL_HOST) ?? '', $host)) {
                continue;
            }

            $vistos[$absoluta] = true;

            $itens[] = [
                'fonte' => $slug,
                'url' => $absoluta,
                'titulo' => Str::limit($texto, 250, ''),
                'resumo' => null,
                'publicado_em' => null,
            ];
        }

        return $itens;
    }

    private function absolutizar(string $href, string $host): ?string
    {
        if (Str::startsWith($href, ['#', 'javascript:', 'mailto:', 'tel:'])) {
            return null;
        }

        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $href;
        }

        if (Str::startsWith($href, '//')) {
            return 'https:'.$href;
        }

        return 'https://'.$host.'/'.ltrim($href, '/');
    }

    private function data(string $bruta): ?Carbon
    {
        $bruta = trim($bruta);

        if ($bruta === '') {
            return null;
        }

        try {
            return Carbon::parse($bruta);
        } catch (\Throwable) {
            return null;
        }
    }
}
