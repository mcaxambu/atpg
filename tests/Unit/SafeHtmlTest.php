<?php

namespace Tests\Unit;

use App\Support\SafeHtml;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Limpeza do HTML escrito no painel.
 *
 * Os campos longos guardam HTML e o portal imprime esse conteudo sem escapar,
 * entao o que passar por aqui vai executar no navegador do leitor. Os testes
 * que mais importam sao os NEGATIVOS: provam que script, endereco de codigo e
 * video de origem estranha nao chegam na pagina.
 */
class SafeHtmlTest extends TestCase
{
    // ----- O que precisa sair -----

    #[Test]
    public function tira_script_com_todo_o_conteudo(): void
    {
        $limpo = SafeHtml::limpar('<p>Nota</p><script>alert(1)</script>');

        $this->assertSame('<p>Nota</p>', $limpo);
        $this->assertStringNotContainsString('alert', $limpo);
    }

    #[Test]
    public function tira_evento_de_clique_e_outros_atributos_de_script(): void
    {
        $limpo = SafeHtml::limpar('<p onclick="roubar()" onmouseover="x()">Texto</p>');

        $this->assertSame('<p>Texto</p>', $limpo);
    }

    #[Test]
    public function tira_endereco_de_codigo_do_link_mas_mantem_a_frase(): void
    {
        $limpo = SafeHtml::limpar('<p><a href="javascript:alert(1)">clique aqui</a></p>');

        $this->assertStringNotContainsString('javascript', $limpo);
        $this->assertStringContainsString('clique aqui', $limpo);
    }

    #[Test]
    public function tira_estilo_que_nao_e_alinhamento(): void
    {
        $limpo = SafeHtml::limpar('<p style="position:fixed;inset:0;background:red">Cobrindo a pagina</p>');

        $this->assertSame('<p>Cobrindo a pagina</p>', $limpo);
    }

    #[Test]
    public function tira_video_incorporado_de_fora_do_youtube(): void
    {
        $limpo = SafeHtml::limpar('<iframe src="https://site-falso.example/login"></iframe><p>Nota</p>');

        $this->assertStringNotContainsString('iframe', $limpo);
        $this->assertStringContainsString('<p>Nota</p>', $limpo);
    }

    #[Test]
    public function tira_tag_de_formulario_inteira(): void
    {
        $limpo = SafeHtml::limpar('<form action="/roubar"><input name="senha"><button>Enviar</button></form><p>Fim</p>');

        $this->assertSame('<p>Fim</p>', $limpo);
    }

    #[Test]
    public function tira_campo_de_texto_solto(): void
    {
        // Sem `<form>` em volta tambem nao vale: um campo de senha no meio de
        // uma noticia imita o formulario de entrada do portal.
        $limpo = SafeHtml::limpar('<p>Entre:</p><input type="password" name="senha">');

        $this->assertSame('<p>Entre:</p>', $limpo);
    }

    #[Test]
    public function mantem_a_caixa_da_lista_de_tarefas_mas_travada(): void
    {
        // A ata importada do Notion traz lista de tarefas, que a conversao de
        // Markdown transforma em caixa de selecao.
        $limpo = SafeHtml::limpar('<ul><li><input type="checkbox" checked>Enviar convites</li></ul>');

        $this->assertStringContainsString('type="checkbox"', $limpo);
        $this->assertStringContainsString('checked', $limpo);
        $this->assertStringContainsString('disabled', $limpo);
        $this->assertStringContainsString('Enviar convites', $limpo);
    }

    #[Test]
    public function trava_a_caixa_mesmo_quando_vem_liberada(): void
    {
        $limpo = SafeHtml::limpar('<p><input type="checkbox"></p>');

        $this->assertStringContainsString('disabled', $limpo);
    }

    #[Test]
    public function tira_imagem_embutida_em_base64(): void
    {
        $limpo = SafeHtml::limpar('<p>Antes</p><img src="data:image/svg+xml;base64,PHN2Zz48L3N2Zz4=">');

        $this->assertStringNotContainsString('data:', $limpo);
        $this->assertStringContainsString('Antes', $limpo);
    }

    #[Test]
    public function desmonta_tag_desconhecida_e_preserva_o_texto(): void
    {
        // Perder a frase ao limpar uma formatacao seria pior do que a
        // formatacao errada: o texto e o que a pessoa escreveu.
        $limpo = SafeHtml::limpar('<p>Comeco <span class="x">meio</span> fim</p>');

        $this->assertSame('<p>Comeco meio fim</p>', $limpo);
    }

    #[Test]
    public function texto_vazio_volta_vazio(): void
    {
        $this->assertSame('', SafeHtml::limpar(null));
        $this->assertSame('', SafeHtml::limpar(''));
    }

    // ----- O que precisa ficar -----

    #[Test]
    public function mantem_a_formatacao_do_texto(): void
    {
        $html = '<h2>Seção</h2><p><strong>negrito</strong>, <em>itálico</em>, <u>sublinhado</u> e <s>riscado</s></p>';

        $this->assertSame($html, SafeHtml::limpar($html));
    }

    #[Test]
    public function mantem_listas_citacao_e_linha(): void
    {
        $html = '<ul><li>um</li></ul><ol><li>dois</li></ol><blockquote><p>citado</p></blockquote><hr>';

        $this->assertSame($html, SafeHtml::limpar($html));
    }

    #[Test]
    public function mantem_o_alinhamento(): void
    {
        $limpo = SafeHtml::limpar('<p style="text-align: center;">Centralizado</p>');

        $this->assertSame('<p style="text-align: center">Centralizado</p>', $limpo);
    }

    #[Test]
    public function mantem_tabela_com_cabecalho(): void
    {
        // A ata gerada pelo sistema traz o quadro de acoes em tabela.
        $html = '<table><thead><tr><th>Ação</th></tr></thead><tbody><tr><td>Publicar</td></tr></tbody></table>';

        $this->assertSame($html, SafeHtml::limpar($html));
    }

    #[Test]
    public function mantem_imagem_do_proprio_site_e_de_endereco_https(): void
    {
        $html = '<p><img src="/storage/editor/foto.jpg" alt="Foto"></p>';
        $this->assertSame($html, SafeHtml::limpar($html));

        $externa = '<p><img src="https://exemplo.org/foto.jpg" alt="Foto"></p>';
        $this->assertSame($externa, SafeHtml::limpar($externa));
    }

    #[Test]
    public function mantem_video_do_youtube(): void
    {
        $limpo = SafeHtml::limpar('<iframe src="https://www.youtube.com/embed/dQw4w9WgXcQ" width="560" height="315"></iframe>');

        $this->assertStringContainsString('youtube.com/embed/dQw4w9WgXcQ', $limpo);
        $this->assertStringContainsString('width="560"', $limpo);
    }

    #[Test]
    public function mantem_link_de_email_e_interno(): void
    {
        $html = '<p><a href="mailto:contato@atpg.net.br">e-mail</a> e <a href="/vagas">vagas</a></p>';

        $this->assertSame($html, SafeHtml::limpar($html));
    }

    #[Test]
    public function acento_sobrevive_a_limpeza(): void
    {
        $limpo = SafeHtml::limpar('<p>Associação de tecnologia de Ponta Grossa — reunião às 19h</p>');

        $this->assertStringContainsString('Associação', $limpo);
        $this->assertStringContainsString('reunião às 19h', $limpo);
        $this->assertStringContainsString('—', $limpo);
    }

    // ----- Equivalencias -----

    #[Test]
    public function troca_tag_antiga_pela_atual(): void
    {
        $limpo = SafeHtml::limpar('<p><b>negrito</b> e <i>italico</i></p>');

        $this->assertSame('<p><strong>negrito</strong> e <em>italico</em></p>', $limpo);
    }

    #[Test]
    public function rebaixa_titulo_de_primeiro_nivel(): void
    {
        // O `<h1>` da pagina e o titulo da noticia, impresso fora do corpo.
        $this->assertSame('<h2>Seção</h2>', SafeHtml::limpar('<h1>Seção</h1>'));
    }
}
