<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Le titulo, resumo e imagem de uma pagina a partir do link — as mesmas
 * metatags Open Graph que o WhatsApp e o LinkedIn usam para montar o cartao.
 *
 * SEGURANCA: quem informa a URL e o usuario do painel, e quem faz a requisicao
 * e o SERVIDOR. Sem trava isso e SSRF: bastaria digitar http://169.254.169.254/
 * (metadados da nuvem) ou um endereco da rede interna para o servidor buscar
 * por dentro e devolver o conteudo na tela. Por isso:
 *
 *   - so http/https;
 *   - o host e resolvido e o IP conferido ANTES de conectar;
 *   - endereco privado, loopback, link-local ou reservado e recusado;
 *   - cada redirecionamento e conferido de novo (redirect e o jeito classico
 *     de furar a checagem inicial);
 *   - tempo e tamanho limitados, para um link lento nao segurar o painel.
 */
class LinkPreview
{
    private const TIMEOUT = 8;

    private const MAX_REDIRECTS = 3;

    /** 2 MB de HTML ja e muito mais do que o <head> de qualquer pagina. */
    private const MAX_BYTES = 2097152;

    /**
     * Resolvedor de DNS. Existe como parametro para que o teste consiga
     * exercitar a leitura das metatags sem depender de DNS de verdade — a
     * checagem de IP acontece ANTES da requisicao, entao `Http::fake` sozinho
     * nao alcanca este trecho.
     *
     * @param  null|callable(string): array<int, string>  $resolvedor
     */
    public function __construct(private $resolvedor = null) {}

    /**
     * @return array{ok: bool, url?: string, title?: string, description?: string, image?: string, site?: string, error?: string}
     */
    public function fetch(string $url): array
    {
        $url = trim($url);

        // Completa o esquema apenas quando NAO ha nenhum ("g1.globo.com/x").
        // Prefixar sem essa checagem transformava "ftp://algo" em
        // "https://ftp://algo", e a recusa por esquema nunca acontecia.
        if (! preg_match('~^[a-z][a-z0-9+.\-]*://~i', $url)) {
            $url = 'https://'.ltrim($url, '/');
        }

        $atual = $url;

        for ($salto = 0; $salto <= self::MAX_REDIRECTS; $salto++) {
            $erro = $this->motivoParaRecusar($atual);

            if ($erro !== null) {
                return ['ok' => false, 'error' => $erro];
            }

            try {
                $resposta = Http::withoutRedirecting()
                    ->timeout(self::TIMEOUT)
                    ->withHeaders([
                        // Alguns sites so entregam as metatags para um navegador.
                        'User-Agent' => 'Mozilla/5.0 (compatible; PortalATPG/1.0; +https://atpg.net.br)',
                        'Accept' => 'text/html,application/xhtml+xml',
                    ])
                    ->get($atual);
            } catch (\Throwable $excecao) {
                Log::info('Pre-visualizacao de link falhou.', ['url' => $atual, 'erro' => $excecao->getMessage()]);

                return ['ok' => false, 'error' => 'Não foi possível acessar o link.'];
            }

            if ($resposta->redirect()) {
                $destino = $resposta->header('Location');

                if (blank($destino)) {
                    return ['ok' => false, 'error' => 'O link redirecionou para um endereço vazio.'];
                }

                // Redireciona para caminho relativo? Resolve contra o atual.
                $atual = $this->resolverDestino($atual, $destino);

                continue;
            }

            if (! $resposta->successful()) {
                return ['ok' => false, 'error' => "O link respondeu {$resposta->status()}."];
            }

            $tipo = (string) $resposta->header('Content-Type');

            if ($tipo !== '' && ! Str::contains($tipo, ['text/html', 'application/xhtml'])) {
                return ['ok' => false, 'error' => 'O link não aponta para uma página web.'];
            }

            return $this->extrair(substr($resposta->body(), 0, self::MAX_BYTES), $atual);
        }

        return ['ok' => false, 'error' => 'O link tem redirecionamentos demais.'];
    }

    /**
     * O endereco pode ser buscado pelo servidor? Exposto para que o download
     * da imagem use exatamente a mesma trava de SSRF, sem duplicar a regra.
     */
    public function fetchableUrl(string $url): bool
    {
        return $this->motivoParaRecusar($url) === null;
    }

    /**
     * Recusa endereco que nao deve ser buscado pelo servidor. Devolve o motivo,
     * ou null quando o endereco e aceitavel.
     */
    private function motivoParaRecusar(string $url): ?string
    {
        $partes = parse_url($url);

        if ($partes === false || blank($partes['host'] ?? null)) {
            return 'Endereço inválido.';
        }

        if (! in_array(strtolower($partes['scheme'] ?? ''), ['http', 'https'], true)) {
            return 'Só é possível buscar endereços http ou https.';
        }

        // IPv6 literal chega entre colchetes ("[::1]"), que nao passam na
        // validacao de IP e fariam o endereco ser tratado como nome de host.
        $host = trim($partes['host'], '[]');

        // Host que ja e um IP, ou nome que resolve para um IP interno.
        $ips = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : $this->resolver($host);

        if ($ips === []) {
            return 'Não foi possível resolver o endereço.';
        }

        foreach ($ips as $ip) {
            if (! $this->ipPublico($ip)) {
                return 'Este endereço aponta para a rede interna e não pode ser buscado.';
            }
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function resolver(string $host): array
    {
        if ($this->resolvedor !== null) {
            return ($this->resolvedor)($host);
        }

        $registros = @dns_get_record($host, DNS_A + DNS_AAAA) ?: [];

        $ips = [];

        foreach ($registros as $registro) {
            foreach (['ip', 'ipv6'] as $campo) {
                if (filled($registro[$campo] ?? null)) {
                    $ips[] = $registro[$campo];
                }
            }
        }

        if ($ips === []) {
            $resolvido = gethostbyname($host);

            if ($resolvido !== $host && filter_var($resolvido, FILTER_VALIDATE_IP)) {
                $ips[] = $resolvido;
            }
        }

        return $ips;
    }

    private function ipPublico(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }

    private function resolverDestino(string $base, string $destino): string
    {
        if (Str::startsWith($destino, ['http://', 'https://'])) {
            return $destino;
        }

        $partes = parse_url($base);
        $raiz = ($partes['scheme'] ?? 'https').'://'.($partes['host'] ?? '');

        if (filled($partes['port'] ?? null)) {
            $raiz .= ':'.$partes['port'];
        }

        return $raiz.'/'.ltrim($destino, '/');
    }

    /**
     * @return array{ok: bool, url: string, title: string, description: string, image: string, site: string}
     */
    private function extrair(string $html, string $url): array
    {
        $meta = $this->metatags($html);

        $titulo = $meta['og:title']
            ?? $meta['twitter:title']
            ?? $this->tagTitle($html)
            ?? '';

        $descricao = $meta['og:description']
            ?? $meta['twitter:description']
            ?? $meta['description']
            ?? '';

        $imagem = $meta['og:image']
            ?? $meta['og:image:url']
            ?? $meta['twitter:image']
            ?? '';

        if (filled($imagem) && ! Str::startsWith($imagem, ['http://', 'https://'])) {
            $imagem = $this->resolverDestino($url, $imagem);
        }

        return [
            'ok' => true,
            'url' => $url,
            'title' => $this->limpar($titulo, 255),
            'description' => $this->limpar($descricao, 500),
            'image' => $imagem,
            'site' => $meta['og:site_name'] ?? (parse_url($url, PHP_URL_HOST) ?: ''),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function metatags(string $html): array
    {
        $anterior = libxml_use_internal_errors(true);

        $doc = new \DOMDocument;
        // HTML de terceiro quase sempre tem erro de marcacao; o parser
        // tolerante e proposital.
        $doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);

        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $tags = [];

        foreach ($doc->getElementsByTagName('meta') as $tag) {
            $chave = $tag->getAttribute('property') ?: $tag->getAttribute('name');
            $valor = $tag->getAttribute('content');

            if ($chave !== '' && $valor !== '') {
                $tags[strtolower($chave)] = $valor;
            }
        }

        return $tags;
    }

    private function tagTitle(string $html): ?string
    {
        return preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $achado)
            ? $achado[1]
            : null;
    }

    private function limpar(string $texto, int $limite): string
    {
        $texto = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return Str::limit(trim(preg_replace('/\s+/u', ' ', $texto)), $limite, '');
    }
}
