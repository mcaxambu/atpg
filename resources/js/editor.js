/*
 * Entrypoint do editor de textos do painel.
 *
 * E um entrypoint separado de proposito, e nao um import dinamico dentro do
 * app.js: o portal tambem roda sob subpasta (/atpg), e o Vite resolve a base
 * dos imports dinamicos em tempo de build — os chunks eram buscados em
 * /build/... sem o prefixo e davam 404. Como entrypoint, a URL sai do @vite,
 * que respeita a raiz resolvida em runtime.
 *
 * O editor e o TinyMCE 7, o mesmo do paginatv, e os campos guardam HTML. Foi
 * uma virada consciente: o formato anterior (Markdown) nao sabe expressar
 * imagem no meio do texto, video incorporado nem alinhamento, entao nenhuma
 * troca de biblioteca daria esses recursos. O que protege o portal e a limpeza
 * por lista de permissao no servidor (App\Support\SafeHtml), aplicada na
 * gravacao E na exibicao — nunca confie no que chega do navegador.
 *
 * O TinyMCE vem do CDN, como no paginatv. A contrapartida e depender de um
 * script de terceiro no painel; se o CDN falhar, o campo continua sendo um
 * textarea comum e o formulario segue funcionando (ver `carregarEditor`).
 */

const TINYMCE = 'https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js';
const IDIOMA = 'https://cdn.jsdelivr.net/npm/tinymce-i18n@26.9.14/langs7/pt_BR.js';

const MAXIMO_BYTES = 5 * 1024 * 1024;

/*
 * Barra igual a do paginatv, que e o padrao que o usuario conhece e aprovou.
 *
 * `blocks` e o seletor de paragrafo/titulo; `removeformat` e o botao que salva
 * quem colou texto do Word com formatacao estranha; `code` mostra o HTML para
 * quem quiser acertar um detalhe na mao.
 */
const BARRA = 'undo redo | blocks | bold italic underline strikethrough'
    + ' | alignleft aligncenter alignright'
    + ' | bullist numlist | link image media table'
    + ' | removeformat code fullscreen';

/*
 * Titulos oferecidos.
 *
 * Sem `h1`: o titulo de nivel 1 da pagina e o titulo da propria noticia, que o
 * portal imprime fora do corpo do texto. A limpeza no servidor rebaixa `h1`
 * para `h2` justamente para nao existirem dois titulos principais.
 */
const BLOCOS = 'Parágrafo=p; Título=h2; Subtítulo=h3; Título menor=h4';

/*
 * Como o texto aparece DENTRO do editor.
 *
 * Vale a pena manter parecido com o portal: editor que mostra uma tipografia e
 * o site que publica outra obriga a pessoa a adivinhar o resultado.
 */
const ESTILO_DO_CONTEUDO = `
    body { font-family: Outfit, system-ui, sans-serif; font-size: 15px; line-height: 1.7; }
    h2 { font-size: 1.3rem; font-weight: 700; margin: 1.4em 0 .5em; }
    h3 { font-size: 1.15rem; font-weight: 700; margin: 1.2em 0 .4em; }
    h4 { font-size: 1.05rem; font-weight: 700; margin: 1.1em 0 .4em; }
    blockquote { margin: 1em 0; padding-left: 14px; border-left: 3px solid #7592ff; }
    img { max-width: 100%; height: auto; }
    iframe { max-width: 100%; }
    table { border-collapse: collapse; width: 100%; }
    table th, table td { border: 1px solid #d0d5dd; padding: .5rem .625rem; }
`;

function tokenCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function urlDeEnvio() {
    return document.querySelector('meta[name="editor-upload-url"]')?.content || '';
}

/**
 * Altura minima do campo.
 *
 * O piso nao pode sair so do atributo `rows`: nem todo textarea do painel tem
 * um (varios usam classe do Tailwind para altura), e o calculo caia no minimo,
 * deixando a area curta demais para uma pagina institucional inteira.
 */
function alturaMinimaDe(textarea) {
    const linhas = Number(textarea.getAttribute('rows') || 0);

    return Math.max(320, linhas * 26);
}

/** Carrega um script uma unica vez, mesmo com varios campos na pagina. */
function carregarScript(url) {
    const existente = document.querySelector(`script[src="${url}"]`);

    if (existente) {
        return existente.promessa;
    }

    const tag = document.createElement('script');

    tag.src = url;
    tag.referrerPolicy = 'origin';
    tag.promessa = new Promise((resolve, reject) => {
        tag.addEventListener('load', resolve);
        tag.addEventListener('error', () => reject(new Error(`falha ao carregar ${url}`)));
    });

    document.head.append(tag);

    return tag.promessa;
}

function enviarImagem(arquivo, nome) {
    if (arquivo.size > MAXIMO_BYTES) {
        return Promise.reject({ message: 'A imagem pode ter no máximo 5 MB.', remove: true });
    }

    const dados = new FormData();
    dados.append('image', arquivo, nome);

    return fetch(urlDeEnvio(), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': tokenCsrf(),
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        body: dados,
    })
        .then(async (resposta) => {
            const corpo = await resposta.json().catch(() => ({}));

            if (! resposta.ok || ! corpo.url) {
                /*
                 * A mensagem do servidor e melhor do que qualquer texto
                 * generico: ela diz se o problema foi tipo de arquivo, tamanho
                 * ou sessao expirada. O 419 do Laravel nao traz mensagem util,
                 * daí o texto proprio.
                 */
                const erro = resposta.status === 419
                    ? 'Sua sessão expirou. Abra o painel de novo e repita o envio.'
                    : (corpo.message || (corpo.errors?.image?.[0]) || 'Não foi possível enviar a imagem.');

                return Promise.reject({ message: erro, remove: true });
            }

            return corpo.url;
        });
}

/** Janela do sistema para escolher o arquivo, usada pelo botao de imagem. */
function escolherArquivo(callback) {
    const campo = document.createElement('input');

    campo.type = 'file';
    campo.accept = 'image/jpeg,image/png,image/gif,image/webp';

    campo.addEventListener('change', () => {
        const arquivo = campo.files && campo.files[0];

        if (! arquivo) {
            return;
        }

        enviarImagem(arquivo, arquivo.name)
            .then((url) => callback(url, { title: arquivo.name, alt: arquivo.name }))
            .catch((erro) => window.alert(erro.message || 'Não foi possível enviar a imagem.'));
    });

    campo.click();
}

function configuracao(textarea) {
    const escuro = document.documentElement.classList.contains('dark');

    return {
        target: textarea,
        license_key: 'gpl',
        language: 'pt_BR',
        language_url: IDIOMA,
        skin: escuro ? 'oxide-dark' : 'oxide',
        content_css: escuro ? 'dark' : 'default',
        menubar: false,
        branding: false,
        promotion: false,
        // O endereco gravado no texto fica completo: mexer nisso faz o TinyMCE
        // reescrever links e quebrar o caminho sob o prefixo /atpg.
        convert_urls: false,
        plugins: 'autoresize code fullscreen image link lists media table wordcount',
        toolbar: BARRA,
        block_formats: BLOCOS,
        min_height: alturaMinimaDe(textarea),
        autoresize_bottom_margin: 24,
        content_style: ESTILO_DO_CONTEUDO,
        placeholder: textarea.getAttribute('placeholder') || '',
        automatic_uploads: true,
        paste_data_images: true,
        block_unsupported_drop: true,
        images_file_types: 'jpeg,jpg,png,gif,webp',
        image_title: true,
        file_picker_types: 'image',
        media_live_embeds: true,
        media_alt_source: false,
        media_poster: false,
        images_upload_handler: (info) => enviarImagem(info.blob(), info.filename()),
        file_picker_callback: (callback) => escolherArquivo(callback),
        setup: (editor) => prepararCampo(editor, textarea),
    };
}

/**
 * Liga o editor ao formulario do Laravel.
 *
 * O TinyMCE só copia o conteudo para o textarea no envio. Isso nao basta aqui:
 * a validacao de campo obrigatorio e a busca por link do formulario de noticia
 * leem o textarea direto, e liam sempre vazio.
 */
function prepararCampo(editor, textarea) {
    // Lista larga de eventos de proposito: `change` so dispara ao criar um
    // ponto de desfazer, e o botao da barra, o colar e o desfazer/refazer
    // mudam o texto sem passar por `keyup`.
    editor.on('change input keyup undo redo ExecCommand SetContent', () => {
        editor.save();
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
        editor.getContainer()?.classList.remove('rich-editor-invalido');
    });

    /*
     * Gancho para quem preenche o campo por fora — a busca por link da noticia
     * escreve no textarea, que agora esta escondido; sem avisar o editor, o
     * valor entrava no formulario e a tela continuava mostrando a caixa vazia.
     */
    textarea.editorInstance = {
        editor,
        setContent: (html) => editor.setContent(html || ''),
        getContent: () => editor.getContent(),
    };

    // Campo obrigatorio escondido trava o envio com "campo nao focavel".
    // A exigencia passa a ser conferida aqui — e no servidor, como sempre.
    if (textarea.hasAttribute('required')) {
        textarea.removeAttribute('required');

        textarea.form?.addEventListener('submit', (evento) => {
            if (editor.getContent({ format: 'text' }).trim() !== '') {
                return;
            }

            evento.preventDefault();
            editor.getContainer()?.classList.add('rich-editor-invalido');
            editor.getContainer()?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            editor.focus();
        });
    }
}

async function iniciar() {
    const campos = document.querySelectorAll('textarea[data-editor]');

    if (campos.length === 0) {
        return;
    }

    try {
        await carregarScript(TINYMCE);
    } catch (erro) {
        /*
         * CDN fora do ar nao pode derrubar o painel: sem o editor, o campo
         * continua sendo um textarea comum. Quem estiver editando um texto que
         * ja tem HTML vai ver as tags, o que e feio mas recuperavel — bem
         * melhor do que um formulario que nao salva.
         */
        console.error('Editor de textos indisponível:', erro);

        return;
    }

    // O pacote de idioma pt_BR deixa este botao em ingles. `addI18n` so
    // acrescenta chaves, entao nada do resto da traducao oficial e perdido.
    window.tinymce.addI18n('pt_BR', { Redo: 'Refazer' });

    campos.forEach((textarea) => window.tinymce.init(configuracao(textarea)));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', iniciar);
} else {
    iniciar();
}
