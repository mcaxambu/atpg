/*
 * Entrypoint do editor visual dos campos longos do painel.
 *
 * E um entrypoint separado de proposito, e nao um import dinamico dentro do
 * app.js: o portal tambem roda sob subpasta (/atpg), e o Vite
 * resolve a base dos imports dinamicos em tempo de build — os chunks eram
 * buscados em /build/... sem o prefixo e davam 404. Como entrypoint, a URL sai
 * do @vite, que respeita a raiz resolvida em runtime.
 *
 * O editor mostra o texto formatado, mas grava MARKDOWN no textarea original,
 * que continua sendo o campo enviado no formulario. Nenhum controller mudou, e
 * o portal segue renderizando com escape de HTML cru.
 */

import Editor from '@toast-ui/editor';
import '@toast-ui/editor/dist/toastui-editor.css';
import '@toast-ui/editor/dist/theme/toastui-editor-dark.css';

/*
 * Interface em portugues. O arquivo de i18n do pacote trata o editor como
 * dependencia externa (6 KB, sem segunda copia da biblioteca) e so registra o
 * idioma; quem escolhe e a opcao `language` de cada instancia.
 */
import '@toast-ui/editor/dist/i18n/pt-br';

/*
 * Alguns rotulos do pacote sao traducao automatica ruim ("colar como mesa"
 * para table, "Traçado" para strike). Registrar o mesmo idioma de novo faz
 * merge: so estas chaves mudam, o resto do arquivo oficial continua valendo.
 */
Editor.setLanguage('pt-BR', {
    Strike: 'Riscado',
    Code: 'Código',
    'Insert CodeBlock': 'Bloco de código',
    'Link text': 'Texto do link',
    'Would you like to paste as table?': 'Deseja colar como tabela?',
    Line: 'Linha divisória',
});

const TOOLBAR = [
    ['heading', 'bold', 'italic', 'strike'],
    ['hr', 'quote'],
    ['ul', 'ol'],
    ['table', 'link'],
    ['code', 'codeblock'],
];

/*
 * Altura minima. A altura real e 'auto': o editor cresce junto com o texto,
 * entao nao existe barra de rolagem dentro da caixa — quem rola e a pagina.
 *
 * O piso nao pode sair do atributo `rows`: nem todo textarea do painel tem um
 * (varios usam classe do Tailwind para altura), e o calculo caia no minimo,
 * deixando a area curta demais para uma pagina institucional inteira.
 */
function alturaMinimaDe(textarea) {
    const linhas = Number(textarea.getAttribute('rows') || 0);

    return `${Math.max(320, linhas * 26)}px`;
}

function montar(textarea) {
    const container = document.createElement('div');
    container.className = 'rich-editor';
    textarea.parentNode.insertBefore(container, textarea);

    // O textarea segue no formulario: e ele que o Laravel recebe.
    textarea.classList.add('rich-editor-source');
    textarea.setAttribute('tabindex', '-1');
    textarea.setAttribute('aria-hidden', 'true');

    const editor = new Editor({
        el: container,
        height: 'auto',
        minHeight: alturaMinimaDe(textarea),
        initialEditType: 'wysiwyg',
        previewStyle: 'vertical',
        initialValue: textarea.value || '',
        usageStatistics: false,
        language: 'pt-BR',
        toolbarItems: TOOLBAR,
        theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
        placeholder: textarea.getAttribute('placeholder') || '',
        events: {
            change: () => {
                textarea.value = editor.getMarkdown();
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
            },
        },
    });

    /*
     * Deixa a instancia acessivel pelo proprio textarea.
     *
     * Quem preenche o campo por fora (a busca por link, por exemplo) escreve
     * no textarea — que agora esta escondido. Sem este gancho, o valor entrava
     * no formulario mas a tela continuava mostrando o editor vazio.
     */
    textarea.editorInstance = editor;

    // Campo obrigatorio escondido trava o envio com "campo nao focavel".
    // A exigencia passa a ser conferida aqui — e no servidor, como sempre.
    if (textarea.hasAttribute('required')) {
        textarea.removeAttribute('required');

        textarea.form?.addEventListener('submit', (evento) => {
            if (textarea.value.trim() === '') {
                evento.preventDefault();
                container.scrollIntoView({ behavior: 'smooth', block: 'center' });
                editor.focus();
                container.classList.add('rich-editor-invalido');
            }
        });

        editor.on('change', () => container.classList.remove('rich-editor-invalido'));
    }
}

function iniciar() {
    document.querySelectorAll('textarea[data-editor]').forEach(montar);
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
} else {
    iniciar();
}
