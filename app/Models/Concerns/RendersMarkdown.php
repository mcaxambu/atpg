<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Converte para HTML os campos escritos no editor do painel.
 *
 * O painel grava Markdown, nunca HTML: o editor visual serializa o que o
 * usuario formatou. Aqui a conversao acontece com as mesmas travas que a
 * noticia ja usava — HTML cru vem escapado e link inseguro e bloqueado.
 * Mesmo sendo conteudo de administrador, uma conta comprometida nao deve
 * virar XSS armazenado para todo visitante do portal.
 */
trait RendersMarkdown
{
    protected function renderMarkdown(?string $texto): string
    {
        if (blank($texto)) {
            return '';
        }

        $html = Str::markdown($texto, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            // soft_break como <br>: o conteudo antigo foi escrito como texto
            // corrido, e sem isto as quebras dentro de um paragrafo sumiriam.
            'renderer' => ['soft_break' => "<br>\n"],
        ]);

        return $this->abrirLinksExternosEmNovaAba($html);
    }

    /**
     * Link para fora do portal abre em outra aba.
     *
     * Sem isso, "ler a materia completa" levava o leitor embora e ele perdia a
     * pagina onde estava. Link interno continua na mesma aba, que e o
     * comportamento esperado dentro do proprio site.
     *
     * `rel="noopener"` nao e enfeite: sem ele a pagina aberta ganha acesso a
     * `window.opener` e consegue trocar o endereco da aba de origem
     * (tabnabbing). `nofollow` porque conteudo apontando para fora nao deve
     * transferir reputacao de busca automaticamente.
     */
    protected function abrirLinksExternosEmNovaAba(string $html): string
    {
        $hostDoPortal = parse_url(config('app.url'), PHP_URL_HOST);

        return preg_replace_callback(
            '/<a\s+href="(https?:\/\/[^"]+)"/i',
            function (array $achado) use ($hostDoPortal) {
                $host = parse_url($achado[1], PHP_URL_HOST);

                if ($host === null || $host === $hostDoPortal) {
                    return $achado[0];
                }

                return $achado[0].' target="_blank" rel="noopener nofollow"';
            },
            $html
        ) ?? $html;
    }

    /**
     * Versao sem formatacao, para resumos e listagens: renderiza e tira as
     * tags, senao o cartao mostraria os asteriscos do Markdown.
     */
    protected function plainFromMarkdown(?string $texto, int $limite = 180): string
    {
        if (blank($texto)) {
            return '';
        }

        $semTags = strip_tags($this->renderMarkdown($texto));

        return Str::limit(trim(html_entity_decode($semTags, ENT_QUOTES)), $limite);
    }
}
