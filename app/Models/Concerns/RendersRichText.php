<?php

namespace App\Models\Concerns;

use App\Support\SafeHtml;
use Illuminate\Support\Str;

/**
 * Prepara para exibicao os campos escritos no editor do painel.
 *
 * O painel grava HTML desde 17/09/2026 (antes era Markdown, que nao sabe
 * expressar imagem no meio do texto, video incorporado nem alinhamento). O
 * portal imprime esse conteudo sem escapar, entao TODO caminho de saida passa
 * por `SafeHtml` — mesmo o conteudo ja tendo sido limpo na gravacao. Limpar
 * duas vezes e barato; um `<script>` gravado antes de uma correcao de regra,
 * ou trazido por um `UPDATE` direto no banco, nao e.
 *
 * O caminho do Markdown continua aqui de proposito: conteudo escrito antes da
 * virada segue no banco em Markdown, e ata importada do Notion nasce assim.
 */
trait RendersRichText
{
    protected function renderRichText(?string $texto): string
    {
        if (blank($texto)) {
            return '';
        }

        $html = self::pareceHtml($texto)
            ? SafeHtml::limpar($texto)
            : self::deMarkdownParaHtml($texto);

        return $this->abrirLinksExternosEmNovaAba($html);
    }

    /**
     * Converte Markdown antigo, com as travas que a noticia sempre teve.
     *
     * `html_input => escape` continua valendo para este caminho: texto que
     * nasceu como Markdown nunca teve permissao de trazer HTML, e afrouxar isso
     * agora abriria justamente o que a limpeza existe para evitar.
     */
    protected static function deMarkdownParaHtml(string $texto): string
    {
        return Str::markdown($texto, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            // soft_break como <br>: o conteudo antigo foi escrito como texto
            // corrido, e sem isto as quebras dentro de um paragrafo sumiriam.
            'renderer' => ['soft_break' => "<br>\n"],
        ]);
    }

    /**
     * Distingue o conteudo novo do antigo pelo proprio texto.
     *
     * Nao existe coluna dizendo o formato de cada registro, e inventar uma
     * obrigaria migrar tudo de uma vez, em produção, antes de qualquer teste.
     * A marca de bloco de HTML e suficiente: Markdown escrito por gente nao
     * comeca com `<p>` nem traz `<h2>`.
     */
    protected static function pareceHtml(string $texto): bool
    {
        return preg_match(
            '#<(?:p|div|h[1-6]|ul|ol|li|table|blockquote|img|iframe|figure|br|strong|em|u|s|a)[\s/>]#i',
            $texto
        ) === 1;
    }

    /**
     * HTML pronto para gravar: e o que os campos do editor recebem.
     *
     * Limpar na ENTRADA tambem, e nao so na saida, evita guardar sujeira no
     * banco — o que vazaria para e-mail, exportacao e qualquer leitura futura
     * que esqueca de limpar.
     */
    protected function limparHtmlRico(?string $texto): ?string
    {
        if ($texto === null) {
            return null;
        }

        if (blank($texto)) {
            return $texto;
        }

        return self::pareceHtml($texto) ? SafeHtml::limpar($texto) : $texto;
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
     * tags, senao o cartao mostraria a marcacao do texto.
     */
    protected function plainFromRichText(?string $texto, int $limite = 180): string
    {
        if (blank($texto)) {
            return '';
        }

        $semTags = strip_tags($this->renderRichText($texto));

        return Str::limit(trim(html_entity_decode($semTags, ENT_QUOTES)), $limite);
    }
}
