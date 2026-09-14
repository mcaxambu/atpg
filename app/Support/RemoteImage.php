<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Baixa a imagem apontada por um link e guarda no disco publico.
 *
 * Guardar em vez de apontar para o servidor de origem e proposital: se o site
 * de origem tirar a imagem do ar ou trocar o endereco, a noticia continua com
 * a capa. E tambem evita que o portal entregue ao visitante uma requisicao
 * para um terceiro.
 *
 * As mesmas travas de SSRF do LinkPreview valem aqui, mais o limite de tamanho
 * e a conferencia de que o arquivo e mesmo uma imagem.
 */
class RemoteImage
{
    private const TIMEOUT = 10;

    private const MAX_BYTES = 5242880; // 5 MB

    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct(private readonly LinkPreview $preview) {}

    /**
     * Devolve o caminho no disco publico, ou null se nao deu para baixar.
     */
    public function store(string $url, string $pasta): ?string
    {
        // Reaproveita a checagem de endereco interno do LinkPreview.
        if (! $this->preview->fetchableUrl($url)) {
            return null;
        }

        try {
            $resposta = Http::timeout(self::TIMEOUT)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; PortalATPG/1.0; +https://atpg.net.br)'])
                ->get($url);
        } catch (\Throwable $excecao) {
            Log::info('Nao foi possivel baixar a imagem do link.', ['url' => $url, 'erro' => $excecao->getMessage()]);

            return null;
        }

        if (! $resposta->successful()) {
            return null;
        }

        $extensao = $this->extensaoDe($resposta);

        if ($extensao === null) {
            return null;
        }

        $conteudo = $resposta->body();

        if (strlen($conteudo) === 0 || strlen($conteudo) > self::MAX_BYTES) {
            return null;
        }

        // Content-Type mente com facilidade; getimagesizefromstring confere o
        // conteudo de verdade.
        if (@getimagesizefromstring($conteudo) === false) {
            return null;
        }

        $caminho = trim($pasta, '/').'/'.Str::random(40).'.'.$extensao;

        Storage::disk('public')->put($caminho, $conteudo);

        return $caminho;
    }

    private function extensaoDe(Response $resposta): ?string
    {
        $tipo = strtolower(trim(explode(';', (string) $resposta->header('Content-Type'))[0]));

        return self::TIPOS[$tipo] ?? null;
    }
}
