<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Limpeza do HTML escrito no painel, por lista do que e permitido.
 *
 * Os campos longos do painel passaram a guardar HTML (antes era Markdown, que
 * nao sabe expressar imagem no meio do texto, video e alinhamento). Isso muda o
 * risco de lugar: o portal imprime esse conteudo com `{!! !!}`, entao qualquer
 * tag que sobreviver aqui vai para a pagina do leitor. Um `<script>` gravado
 * numa descricao de empresa seria executado em todo visitante do perfil.
 *
 * A regra e LISTA DO QUE ENTRA, nunca lista do que sai: tag que ninguem
 * previu e recusada por padrao. Filtro ao contrario envelhece mal — basta uma
 * tag nova no HTML para abrir um buraco.
 *
 * Nao usamos pacote externo (HTMLPurifier e afins) de proposito: a publicacao
 * do ATPG nao leva a pasta `vendor` para o servidor, entao dependencia nova
 * exigiria mexer no fluxo de deploy. Aqui basta o DOM que o PHP ja tem.
 */
final class SafeHtml
{
    /**
     * Tags aceitas e, em cada uma, os atributos aceitos.
     *
     * `style` aparece so onde o alinhamento faz sentido, e mesmo ali passa pelo
     * filtro de `text-align` — nao e um `style` livre.
     */
    private const PERMITIDAS = [
        'p' => ['style'],
        'br' => [],
        'strong' => [],
        'em' => [],
        'u' => [],
        's' => [],
        'h2' => ['style'],
        'h3' => ['style'],
        'h4' => ['style'],
        'ul' => [],
        'ol' => ['start'],
        'li' => [],
        'blockquote' => [],
        'hr' => [],
        'a' => ['href', 'title'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'figure' => [],
        'figcaption' => [],
        'table' => [],
        'thead' => [],
        'tbody' => [],
        'tfoot' => [],
        'tr' => [],
        'th' => ['colspan', 'rowspan', 'style'],
        'td' => ['colspan', 'rowspan', 'style'],
        'iframe' => ['src', 'width', 'height', 'title', 'allow', 'allowfullscreen'],
        'code' => [],
        'pre' => [],
        /*
         * Caixa de selecao existe por causa da ata importada do Notion: lista
         * de tarefas em Markdown (`- [x] feito`) vira `<input type="checkbox">`
         * na conversao, e sem isso o quadro de tarefas da ata perdia as marcas.
         *
         * E o UNICO campo de formulario aceito, sempre travado (ver
         * `campoAceito`): um `<input type="text">` no meio de uma noticia
         * poderia imitar um formulario de login do portal.
         */
        'input' => ['type', 'checked', 'disabled'],
    ];

    /**
     * Tags que saem com todo o conteudo dentro.
     *
     * A diferenca importa: `<span>` desconhecido perde a tag mas o TEXTO fica
     * (senao limpar uma formatacao apagaria a frase). Ja `<script>` tem de sair
     * inteiro — deixar o "texto" de um script na pagina nao ajuda ninguem, e
     * `<style>` viraria CSS solto no meio da materia.
     */
    private const REMOVER_COM_CONTEUDO = [
        'script', 'style', 'noscript', 'template', 'object', 'embed', 'applet',
        'form', 'button', 'select', 'textarea', 'option', 'label',
        'audio', 'video', 'source', 'track', 'svg', 'math', 'canvas',
        'link', 'meta', 'base', 'title', 'head', 'frame', 'frameset',
    ];

    /**
     * Equivalencias do que o editor as vezes manda: mesmo significado, tag
     * diferente. `<b>` vira `<strong>` e `<h1>` vira `<h2>` porque o `<h1>` da
     * pagina e o titulo da noticia, impresso fora do corpo do texto.
     */
    private const EQUIVALENTES = [
        'b' => 'strong',
        'i' => 'em',
        'strike' => 's',
        'del' => 's',
        'ins' => 'u',
        'h1' => 'h2',
        'h5' => 'h4',
        'h6' => 'h4',
    ];

    private const ALINHAMENTOS = ['left', 'center', 'right', 'justify'];

    /** Esquemas aceitos em link. `javascript:` e `data:` ficam de fora. */
    private const ESQUEMAS_DE_LINK = ['http', 'https', 'mailto', 'tel'];

    public static function limpar(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $documento = new DOMDocument('1.0', 'UTF-8');

        /*
         * O `<meta charset>` e a raiz artificial existem por limitacao do
         * parser: sem o meta, acento vira lixo; sem uma raiz unica, o libxml
         * inventa `<p>` em volta de tabela e a estrutura muda sozinha.
         */
        $anterior = libxml_use_internal_errors(true);

        $documento->loadHTML(
            '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"><div id="raiz-limpeza">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );

        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $raiz = $documento->getElementById('raiz-limpeza');

        if (! $raiz instanceof DOMElement) {
            return '';
        }

        self::limparFilhos($raiz);

        $limpo = '';

        foreach (iterator_to_array($raiz->childNodes) as $filho) {
            $limpo .= $documento->saveHTML($filho);
        }

        return trim($limpo);
    }

    private static function limparFilhos(DOMNode $pai): void
    {
        // Copia da lista: remover e desmontar nós altera `childNodes` no meio
        // da volta, e a iteracao pularia elementos.
        foreach (iterator_to_array($pai->childNodes) as $no) {
            if ($no instanceof DOMComment) {
                $no->parentNode?->removeChild($no);

                continue;
            }

            if (! $no instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($no->nodeName);

            if (in_array($tag, self::REMOVER_COM_CONTEUDO, true)) {
                $no->parentNode?->removeChild($no);

                continue;
            }

            if (isset(self::EQUIVALENTES[$tag])) {
                $tag = self::renomear($no, self::EQUIVALENTES[$tag]);
            }

            if (! array_key_exists($tag, self::PERMITIDAS)) {
                self::limparFilhos($no);
                self::desmontar($no);

                continue;
            }

            self::limparAtributos($no, $tag);

            if (! self::enderecoAceito($no, $tag)) {
                $no->parentNode?->removeChild($no);

                continue;
            }

            self::limparFilhos($no);
        }
    }

    /**
     * Troca a tag mantendo os filhos, e devolve o nome novo.
     *
     * O DOM do PHP nao renomeia elemento: cria-se o novo, mudam-se os filhos de
     * lugar e troca-se um pelo outro.
     */
    private static function renomear(DOMElement $no, string $tagNova): string
    {
        $novo = $no->ownerDocument->createElement($tagNova);

        foreach (iterator_to_array($no->attributes) as $atributo) {
            $novo->setAttribute($atributo->nodeName, $atributo->nodeValue);
        }

        while ($no->firstChild) {
            $novo->appendChild($no->firstChild);
        }

        $no->parentNode?->replaceChild($novo, $no);

        // Quem chamou continua trabalhando no nó novo.
        $no = $novo;

        return $tagNova;
    }

    /** Tira a tag e deixa o conteudo no lugar dela. */
    private static function desmontar(DOMElement $no): void
    {
        $pai = $no->parentNode;

        if ($pai === null) {
            return;
        }

        while ($no->firstChild) {
            $pai->insertBefore($no->firstChild, $no);
        }

        $pai->removeChild($no);
    }

    private static function limparAtributos(DOMElement $no, string $tag): void
    {
        $aceitos = self::PERMITIDAS[$tag];

        foreach (iterator_to_array($no->attributes) as $atributo) {
            $nome = strtolower($atributo->nodeName);

            if (! in_array($nome, $aceitos, true)) {
                $no->removeAttribute($atributo->nodeName);

                continue;
            }

            if ($nome === 'style') {
                $alinhamento = self::alinhamentoDe($atributo->nodeValue ?? '');

                if ($alinhamento === null) {
                    $no->removeAttribute('style');
                } else {
                    $no->setAttribute('style', "text-align: {$alinhamento}");
                }
            }

            if (in_array($nome, ['width', 'height', 'start', 'colspan', 'rowspan'], true)
                && ! ctype_digit((string) $atributo->nodeValue)) {
                $no->removeAttribute($atributo->nodeName);
            }
        }
    }

    /**
     * Do `style` inteiro aproveitamos so o alinhamento.
     *
     * Deixar `style` livre passar seria tao ruim quanto deixar `<script>`:
     * `position: fixed` com fundo cobre a pagina toda, e o conteudo do painel
     * nao precisa disso.
     */
    private static function alinhamentoDe(string $style): ?string
    {
        if (preg_match('/text-align\s*:\s*(left|center|right|justify)/i', $style, $achado) !== 1) {
            return null;
        }

        $valor = strtolower($achado[1]);

        return in_array($valor, self::ALINHAMENTOS, true) ? $valor : null;
    }

    private static function enderecoAceito(DOMElement $no, string $tag): bool
    {
        if ($tag === 'a') {
            if (! $no->hasAttribute('href')) {
                return true;
            }

            if (! self::linkAceito($no->getAttribute('href'))) {
                // O link sai, o texto fica: quem escreveu perde o endereco
                // suspeito, nao a frase.
                $no->removeAttribute('href');
            }

            return true;
        }

        if ($tag === 'img') {
            return self::imagemAceita($no->getAttribute('src'));
        }

        if ($tag === 'iframe') {
            return self::videoAceito($no->getAttribute('src'));
        }

        if ($tag === 'input') {
            return self::campoAceito($no);
        }

        return true;
    }

    /**
     * Do formulario, so a caixa de selecao da lista de tarefas — e travada.
     *
     * `disabled` e forcado aqui, e nao apenas aceito: caixa que o leitor pode
     * marcar sugere que a marcacao fica gravada, e nao fica. O estado vem do
     * texto da ata.
     */
    private static function campoAceito(DOMElement $no): bool
    {
        if (strtolower($no->getAttribute('type')) !== 'checkbox') {
            return false;
        }

        $no->setAttribute('disabled', 'disabled');

        return true;
    }

    private static function linkAceito(string $endereco): bool
    {
        $endereco = trim($endereco);

        // Endereco do proprio site ou ancora na pagina.
        if (str_starts_with($endereco, '/') || str_starts_with($endereco, '#')) {
            return true;
        }

        $esquema = strtolower((string) parse_url($endereco, PHP_URL_SCHEME));

        return in_array($esquema, self::ESQUEMAS_DE_LINK, true);
    }

    private static function imagemAceita(string $endereco): bool
    {
        $endereco = trim($endereco);

        if (str_starts_with($endereco, '/')) {
            return true;
        }

        $esquema = strtolower((string) parse_url($endereco, PHP_URL_SCHEME));

        // `data:` fica fora: imagem colada e enviada para o disco pelo proprio
        // editor e volta como endereco, entao base64 aqui so serve para inflar
        // o banco (ou esconder outra coisa dentro de um `src`).
        return in_array($esquema, ['http', 'https'], true);
    }

    /**
     * Video incorporado: so YouTube, e so o endereco de incorporacao.
     *
     * `iframe` e a tag mais perigosa da lista — ela traz uma pagina de fora
     * para dentro da nossa. Liberar qualquer endereco permitiria incorporar
     * uma tela de login falsa no meio de uma noticia do portal.
     */
    private static function videoAceito(string $endereco): bool
    {
        return preg_match(
            '#^https://(?:www\.)?(?:youtube\.com|youtube-nocookie\.com)/embed/[A-Za-z0-9_-]{6,}#',
            trim($endereco)
        ) === 1;
    }
}
