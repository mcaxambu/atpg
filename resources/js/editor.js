/*
 * Entrypoint do editor visual dos campos longos do painel.
 *
 * E um entrypoint separado de proposito, e nao um import dinamico dentro do
 * app.js: o portal tambem roda sob subpasta (/atpg), e o Vite resolve a base
 * dos imports dinamicos em tempo de build — os chunks eram buscados em
 * /build/... sem o prefixo e davam 404. Como entrypoint, a URL sai do @vite,
 * que respeita a raiz resolvida em runtime.
 *
 * O editor mostra o texto formatado, mas grava MARKDOWN no textarea original,
 * que continua sendo o campo enviado no formulario. Essa decisao e o que torna
 * a troca de biblioteca barata: nenhum controller, model ou view do portal
 * muda, o conteudo ja publicado continua valendo, o site segue convertendo com
 * o HTML cru escapado, e a ata gerada pelo sistema (que sai em Markdown, com
 * tabela de acoes) continua legivel dos dois lados.
 *
 * A barra de ferramentas e nossa, botao por botao. O editor anterior trazia
 * uma barra pronta cujo menu de titulo abria sozinho ao clicar na area de
 * texto; aqui nao existe menu suspenso nenhum.
 */

import { Editor } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { Placeholder } from '@tiptap/extensions';
import { TableKit } from '@tiptap/extension-table';
import { Markdown } from 'tiptap-markdown';

/*
 * Barra principal, em grupos separados por um tracinho.
 *
 * Titulo aqui e `##` e subtitulo e `###`: o `#` fica reservado para o titulo
 * da propria pagina, que o portal ja imprime fora do corpo do texto. Titulo
 * de nivel mais fundo existe no conteudo antigo e continua sendo preservado na
 * gravacao — so nao tem botao, porque ninguem escrevia assim.
 */
const GRUPOS = [
    [
        {
            rotulo: 'Título',
            dica: 'Título de seção',
            acao: (e) => e.chain().focus().toggleHeading({ level: 2 }).run(),
            ativo: (e) => e.isActive('heading', { level: 2 }),
        },
        {
            rotulo: 'Subtítulo',
            dica: 'Subtítulo, dentro de uma seção',
            acao: (e) => e.chain().focus().toggleHeading({ level: 3 }).run(),
            ativo: (e) => e.isActive('heading', { level: 3 }),
        },
    ],
    [
        {
            rotulo: 'N',
            dica: 'Negrito (Ctrl+B)',
            classe: 'rich-editor-negrito',
            acao: (e) => e.chain().focus().toggleBold().run(),
            ativo: (e) => e.isActive('bold'),
        },
        {
            rotulo: 'I',
            dica: 'Itálico (Ctrl+I)',
            classe: 'rich-editor-italico',
            acao: (e) => e.chain().focus().toggleItalic().run(),
            ativo: (e) => e.isActive('italic'),
        },
        {
            rotulo: 'S',
            dica: 'Riscado',
            classe: 'rich-editor-riscado',
            acao: (e) => e.chain().focus().toggleStrike().run(),
            ativo: (e) => e.isActive('strike'),
        },
    ],
    [
        {
            rotulo: 'Lista',
            dica: 'Lista com marcadores',
            acao: (e) => e.chain().focus().toggleBulletList().run(),
            ativo: (e) => e.isActive('bulletList'),
        },
        {
            rotulo: 'Numerada',
            dica: 'Lista numerada',
            acao: (e) => e.chain().focus().toggleOrderedList().run(),
            ativo: (e) => e.isActive('orderedList'),
        },
    ],
    [
        {
            rotulo: 'Citação',
            dica: 'Destacar um trecho citado',
            acao: (e) => e.chain().focus().toggleBlockquote().run(),
            ativo: (e) => e.isActive('blockquote'),
        },
        {
            rotulo: 'Linha',
            dica: 'Linha divisória entre assuntos',
            acao: (e) => e.chain().focus().setHorizontalRule().run(),
        },
    ],
    [
        {
            rotulo: 'Link',
            dica: 'Transformar o texto selecionado em link',
            nome: 'link',
            ativo: (e) => e.isActive('link'),
        },
        {
            rotulo: 'Tabela',
            dica: 'Inserir uma tabela de 3 colunas',
            acao: (e) => e.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
        },
    ],
];

/*
 * Botoes que so fazem sentido com o cursor dentro de uma tabela. Ficam numa
 * segunda faixa, que aparece e desaparece conforme o cursor: manter "excluir
 * coluna" sempre visivel numa noticia sem tabela e ruido.
 */
const BOTOES_TABELA = [
    { rotulo: '+ linha', dica: 'Adicionar linha abaixo', acao: (e) => e.chain().focus().addRowAfter().run() },
    { rotulo: '+ coluna', dica: 'Adicionar coluna à direita', acao: (e) => e.chain().focus().addColumnAfter().run() },
    { rotulo: '− linha', dica: 'Excluir a linha do cursor', acao: (e) => e.chain().focus().deleteRow().run() },
    { rotulo: '− coluna', dica: 'Excluir a coluna do cursor', acao: (e) => e.chain().focus().deleteColumn().run() },
    { rotulo: 'Excluir tabela', dica: 'Excluir a tabela inteira', acao: (e) => e.chain().focus().deleteTable().run() },
];

/*
 * Altura minima.
 *
 * O piso nao pode sair so do atributo `rows`: nem todo textarea do painel tem
 * um (varios usam classe do Tailwind para altura), e o calculo caia no minimo,
 * deixando a area curta demais para uma pagina institucional inteira.
 *
 * ATENCAO: este piso vale para a AREA EDITAVEL, nao para a moldura. Com a
 * altura na moldura, um campo vazio desenhava uma caixa alta com poucos pixels
 * clicaveis no topo: clicar no meio nao levava o cursor para o texto, e o que
 * a pessoa digitava ia para o campo focado antes.
 */
function alturaMinimaDe(textarea) {
    const linhas = Number(textarea.getAttribute('rows') || 0);

    return `${Math.max(320, linhas * 26)}px`;
}

function criarBotao({ rotulo, dica, classe }) {
    const botao = document.createElement('button');
    botao.type = 'button';
    botao.className = `rich-editor-botao ${classe || ''}`.trim();
    botao.title = dica || rotulo;
    botao.textContent = rotulo;

    /*
     * O clique nao pode tirar o foco do texto.
     *
     * Sem isto, apertar "Negrito" com um trecho selecionado primeiro esvazia a
     * selecao (o botao recebe o foco) e o formato acaba aplicado no vazio. O
     * `preventDefault` no mousedown mantem cursor e selecao onde estavam; o
     * clique em si continua acontecendo normalmente.
     */
    botao.addEventListener('mousedown', (evento) => evento.preventDefault());

    return botao;
}

/*
 * Barra de endereco do link.
 *
 * Fica escondida até alguem pedir, e reaproveita o endereco do link em que o
 * cursor esta — editar um link existente e o caso comum, nao criar um novo. O
 * Enter aplica e o Esc fecha, porque quem escreve texto nao vai no mouse para
 * confirmar um endereco.
 */
function criarBarraDeLink(editor) {
    const barra = document.createElement('div');
    barra.className = 'rich-editor-link';
    barra.hidden = true;

    const campo = document.createElement('input');
    campo.type = 'url';
    campo.placeholder = 'https://…';
    campo.className = 'rich-editor-link-campo';

    const aplicar = criarBotao({ rotulo: 'Aplicar', dica: 'Aplicar o link' });
    const remover = criarBotao({ rotulo: 'Remover', dica: 'Remover o link' });
    const fechar = criarBotao({ rotulo: 'Cancelar', dica: 'Fechar sem alterar' });

    barra.append(campo, aplicar, remover, fechar);

    const esconder = () => {
        barra.hidden = true;
        editor.commands.focus();
    };

    /*
     * Trecho que estava selecionado quando a barra abriu.
     *
     * Guardar e restaurar e obrigatorio: ao tirar o foco do texto para digitar
     * o endereco, a selecao colapsa, e `extendMarkRange` sem trecho selecionado
     * nao tem onde aplicar o link — o endereco era aceito e nada acontecia.
     */
    let trecho = null;

    const gravar = () => {
        const endereco = campo.value.trim();
        const comSelecao = () => {
            const acao = editor.chain().focus();

            return trecho ? acao.setTextSelection(trecho) : acao;
        };

        if (endereco === '') {
            comSelecao().extendMarkRange('link').unsetLink().run();
        } else {
            comSelecao().extendMarkRange('link').setLink({ href: endereco }).run();
        }

        barra.hidden = true;
    };

    aplicar.addEventListener('click', gravar);
    fechar.addEventListener('click', esconder);
    remover.addEventListener('click', () => {
        campo.value = '';
        gravar();
    });

    campo.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter') {
            evento.preventDefault();
            gravar();
        }

        if (evento.key === 'Escape') {
            evento.preventDefault();
            esconder();
        }
    });

    barra.abrir = () => {
        const { from, to } = editor.state.selection;

        trecho = { from, to };
        campo.value = editor.getAttributes('link').href || '';
        barra.hidden = false;
        campo.focus();
        campo.select();
    };

    return barra;
}

function montarBarra(editor, aoTrocarEstado) {
    const barra = document.createElement('div');
    barra.className = 'rich-editor-barra';

    const barraDeLink = criarBarraDeLink(editor);
    const sincronizar = [];

    GRUPOS.forEach((grupo, indice) => {
        if (indice > 0) {
            const separador = document.createElement('span');
            separador.className = 'rich-editor-separador';
            separador.setAttribute('aria-hidden', 'true');
            barra.append(separador);
        }

        grupo.forEach((item) => {
            const botao = criarBotao(item);

            botao.addEventListener('click', () => {
                if (item.nome === 'link') {
                    barraDeLink.abrir();
                } else {
                    item.acao(editor);
                }

                aoTrocarEstado();
            });

            if (item.ativo) {
                sincronizar.push(() => {
                    const ligado = item.ativo(editor);
                    botao.classList.toggle('rich-editor-ligado', ligado);
                    botao.setAttribute('aria-pressed', ligado ? 'true' : 'false');
                });
            }

            barra.append(botao);
        });
    });

    const faixaTabela = document.createElement('div');
    faixaTabela.className = 'rich-editor-barra rich-editor-barra-tabela';
    faixaTabela.hidden = true;

    BOTOES_TABELA.forEach((item) => {
        const botao = criarBotao(item);
        botao.addEventListener('click', () => {
            item.acao(editor);
            aoTrocarEstado();
        });
        faixaTabela.append(botao);
    });

    sincronizar.push(() => {
        faixaTabela.hidden = ! editor.isActive('table');
    });

    return { barra, faixaTabela, barraDeLink, sincronizar };
}

function montar(textarea) {
    const moldura = document.createElement('div');
    moldura.className = 'rich-editor';
    moldura.style.setProperty('--rich-editor-altura', alturaMinimaDe(textarea));

    const area = document.createElement('div');
    area.className = 'rich-editor-area';

    textarea.parentNode.insertBefore(moldura, textarea);

    // O textarea segue no formulario: e ele que o Laravel recebe.
    textarea.classList.add('rich-editor-source');
    textarea.setAttribute('tabindex', '-1');
    textarea.setAttribute('aria-hidden', 'true');

    const editor = new Editor({
        element: area,
        content: textarea.value || '',
        extensions: [
            StarterKit.configure({
                // O link entra pela barra, com endereco conferido; abrir no
                // clique dentro do editor so tira a pessoa do formulario.
                link: { openOnClick: false, autolink: true },
            }),
            TableKit.configure({ table: { resizable: false } }),
            Placeholder.configure({
                placeholder: textarea.getAttribute('placeholder') || 'Escreva aqui…',
            }),
            Markdown.configure({
                // `linkify: false` porque o autolink do StarterKit ja cuida
                // disso na digitacao; ligar os dois duplica marcacao no texto.
                linkify: false,
                breaks: false,
                transformPastedText: true,
            }),
        ],
        onUpdate: () => {
            textarea.value = editor.storage.markdown.getMarkdown();
            textarea.dispatchEvent(new Event('input', { bubbles: true }));
            moldura.classList.remove('rich-editor-invalido');
        },
    });

    const { barra, faixaTabela, barraDeLink, sincronizar } = montarBarra(editor, () => {
        sincronizar.forEach((f) => f());
    });

    // A barra de endereco do link TEM de entrar no documento: sem isso o
    // `focus()` no campo nao acontece (elemento solto nao recebe foco) e o
    // endereco digitado cai dentro do texto, por cima da selecao.
    moldura.append(barra, barraDeLink, faixaTabela, area);
    editor.on('selectionUpdate', () => sincronizar.forEach((f) => f()));
    editor.on('transaction', () => sincronizar.forEach((f) => f()));
    sincronizar.forEach((f) => f());

    /*
     * Deixa a instancia acessivel pelo proprio textarea, com a mesma API que a
     * pagina da noticia usa (`setMarkdown`).
     *
     * Quem preenche o campo por fora — a busca por link, por exemplo — escreve
     * no textarea, que agora esta escondido. Sem este gancho, o valor entrava
     * no formulario mas a tela continuava mostrando o editor vazio.
     */
    textarea.editorInstance = {
        editor,
        setMarkdown: (texto) => editor.commands.setContent(texto || ''),
        getMarkdown: () => editor.storage.markdown.getMarkdown(),
    };

    // Campo obrigatorio escondido trava o envio com "campo nao focavel".
    // A exigencia passa a ser conferida aqui — e no servidor, como sempre.
    if (textarea.hasAttribute('required')) {
        textarea.removeAttribute('required');

        textarea.form?.addEventListener('submit', (evento) => {
            if (textarea.value.trim() === '') {
                evento.preventDefault();
                moldura.scrollIntoView({ behavior: 'smooth', block: 'center' });
                editor.commands.focus();
                moldura.classList.add('rich-editor-invalido');
            }
        });
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
