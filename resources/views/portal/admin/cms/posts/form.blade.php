@extends('layouts.admin')

@section('title', ($post->exists ? 'Editar notícia' : 'Nova notícia') . ' | Portal Associação Tech PG')
@section('page_heading', $post->exists ? 'Editar notícia' : 'Nova notícia')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <span class="text-xs font-semibold uppercase tracking-wide text-brand-500">CMS do portal</span>
            <h2 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $post->exists ? 'Editar notícia' : 'Nova notícia' }}</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Atualize comunicados, eventos, conquistas, parcerias e conteúdos institucionais.</p>
        </div>
        <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.posts.index') }}">Voltar</a>
    </div>


    <form class="space-y-6" method="post" action="{{ $post->exists ? route('admin.cms.posts.update', $post) : route('admin.cms.posts.store') }}" enctype="multipart/form-data">
        @csrf
        @if ($post->exists) @method('put') @endif

        {{--
            Busca titulo, resumo e capa a partir das metatags Open Graph do
            link — as mesmas que o WhatsApp e o LinkedIn usam no cartao de
            pre-visualizacao. O editor confere e ajusta antes de salvar.
        --}}
        <section class="rounded-2xl border border-brand-200 bg-brand-25 p-5 dark:border-brand-500/30 dark:bg-brand-500/[0.06]"
                 x-data="buscadorDeLink()">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Começar a partir de um link</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Cole o endereço da matéria e o painel preenche título, resumo e capa sozinho.
                    Você revisa tudo antes de salvar.
                </p>
            </div>

            <div class="flex flex-col gap-3 sm:flex-row">
                <input type="url" x-model="url" @keydown.enter.prevent="buscar()"
                       placeholder="https://exemplo.com.br/materia"
                       class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">

                <button type="button" @click="buscar()" :disabled="carregando || ! url"
                        class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 text-sm font-medium text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:opacity-60">
                    <span x-show="! carregando">Buscar do link</span>
                    <span x-show="carregando" x-cloak>Buscando…</span>
                </button>
            </div>

            <p x-show="erro" x-cloak x-text="erro"
               class="mt-3 rounded-lg border border-error-500/30 bg-error-50 px-3 py-2 text-sm text-error-700 dark:bg-error-500/10 dark:text-error-400"></p>

            <div x-show="achou" x-cloak class="mt-4 rounded-lg border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex gap-3">
                    <img x-show="imagem" :src="imagem" alt=""
                         class="h-20 w-32 shrink-0 rounded-lg object-cover">
                    <div class="min-w-0">
                        <strong class="block truncate text-sm text-gray-800 dark:text-white/90" x-text="titulo"></strong>
                        <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400" x-text="site"></span>
                        <span class="mt-1 block text-xs text-success-600 dark:text-success-500">
                            Campos preenchidos. Revise abaixo antes de salvar.
                        </span>
                    </div>
                </div>
            </div>

            {{-- A capa e baixada no servidor ao salvar, e nao agora: assim uma
                 busca abandonada nao deixa arquivo orfao no disco. --}}
            <input type="hidden" name="cover_image_url" x-model="imagem">
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Conteudo</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">O titulo gera automaticamente a URL pública da notícia.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Titulo</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="title" value="{{ old('title', $post->title) }}" required>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Categoria</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="category" value="{{ old('category', $post->category ?: 'Notícia') }}" required>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Fonte <small class="text-gray-400">(opcional)</small></span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                           name="source_name" value="{{ old('source_name', $post->source_name) }}" placeholder="Ex.: Diário dos Campos">
                </label>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Link da matéria original <small class="text-gray-400">(opcional)</small></span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                           type="url" name="source_url" value="{{ old('source_url', $post->source_url) }}">
                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">
                        Preenchido pela busca acima. Quando informado, a notícia mostra o crédito e um link para a fonte.
                    </span>
                </label>
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Data de publicação</span>
                    <input class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="published_at" type="datetime-local" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}">
                </label>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Resumo</span>
                    <textarea class="min-h-24 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="excerpt" maxlength="500">{{ old('excerpt', $post->excerpt) }}</textarea>
                </label>
                <label class="block lg:col-span-2">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Texto da notícia</span>
                    <textarea class="min-h-72 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" name="body" data-editor required>{{ old('body', $post->body) }}</textarea>
                    <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">Escreva direto na caixa e use os botões acima para formatar. O texto aparece aqui como vai sair no portal; para um novo parágrafo, dê Enter.</span>
                </label>
            </div>
        </section>

        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="mb-5">
                <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Publicação</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Controle capa e visibilidade no portal.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Imagem de capa</span>
                    <input class="block w-full rounded-lg border border-gray-300 bg-transparent text-sm text-gray-800 file:mr-5 file:border-0 file:bg-gray-100 file:px-4 file:py-3 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:file:bg-white/[0.03] dark:file:text-gray-300" name="cover_image" type="file" accept=".jpg,.jpeg,.png,.webp,image/*">
                </label>
                <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300">
                    <input class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" type="checkbox" name="is_published" value="1" @checked(old('is_published', $post->exists ? $post->is_published : false))>
                    Publicar no portal
                </label>

                <label class="flex items-start gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm font-medium text-gray-700 dark:border-gray-800 dark:bg-white/[0.03] dark:text-gray-300">
                    <input class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured))>
                    <span>
                        Destaque
                        <span class="mt-0.5 block text-xs font-normal text-gray-500 dark:text-gray-400">
                            Fica sempre em primeiro na listagem e na home, independente da data.
                            Se marcar mais de uma, elas vêm primeiro na ordem de publicação.
                        </span>
                    </span>
                </label>
            </div>

            @if ($post->cover_image)
                <div class="mt-5 flex items-center gap-4 rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-800 dark:bg-white/[0.03]">
                    <img class="h-20 w-32 rounded-lg object-cover" src="{{ asset('storage/' . $post->cover_image) }}" alt="Capa {{ $post->title }}">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Capa atual</span>
                </div>
            @endif
        </section>

        <div class="flex flex-wrap justify-end gap-3">
            <a class="inline-flex items-center justify-center rounded-lg border border-gray-200 px-5 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]" href="{{ route('admin.cms.posts.index') }}">Cancelar</a>
            <button class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-2.5 text-sm font-medium text-white shadow-theme-xs hover:bg-brand-600" type="submit">Salvar notícia</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    @vite('resources/js/editor.js')

    <script>
        /*
            Busca as metatags do link e preenche o formulario.

            O corpo nasce como MARKDOWN estruturado, e nao como texto solto:
            e o mesmo formato que o editor visual e o portal ja usam, entao a
            noticia sai formatada no padrao do site. O texto e um ponto de
            partida — a ideia e a associacao escrever o proprio comentario e
            deixar o link para a materia completa, em vez de republicar a
            materia inteira de terceiro.
        */
        function buscadorDeLink() {
            return {
                url: '',
                carregando: false,
                erro: '',
                achou: false,
                titulo: '',
                site: '',
                imagem: '',

                async buscar() {
                    if (!this.url) return;

                    this.carregando = true;
                    this.erro = '';
                    this.achou = false;

                    try {
                        const resposta = await fetch(@json(route('admin.cms.posts.preview')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            },
                            body: JSON.stringify({ url: this.url }),
                        });

                        const dados = await resposta.json();

                        if (!resposta.ok || !dados.ok) {
                            this.erro = dados.error
                                || (dados.errors && Object.values(dados.errors)[0][0])
                                || 'Não foi possível ler este link.';
                            return;
                        }

                        this.titulo = dados.title || '';
                        this.site = dados.site || '';
                        this.imagem = dados.image || '';
                        this.achou = true;

                        this.preencher(dados);
                    } catch (e) {
                        this.erro = 'Falha ao consultar o link. Tente novamente.';
                    } finally {
                        this.carregando = false;
                    }
                },

                preencher(dados) {
                    const campo = (nome) => this.$root.closest('form').querySelector(`[name="${nome}"]`);

                    // Campo ja preenchido pelo editor nao e sobrescrito.
                    const preencherSeVazio = (nome, valor) => {
                        const alvo = campo(nome);
                        if (alvo && valor && !alvo.value.trim()) alvo.value = valor;
                    };

                    preencherSeVazio('title', dados.title);
                    preencherSeVazio('excerpt', dados.description);
                    preencherSeVazio('source_name', dados.site);

                    const link = campo('source_url');
                    if (link && !link.value.trim()) link.value = dados.url;

                    this.preencherCorpo(dados);
                },

                preencherCorpo(dados) {
                    const textarea = this.$root.closest('form').querySelector('[name="body"]');
                    if (!textarea || textarea.value.trim()) return;

                    const partes = [];

                    if (dados.description) partes.push(dados.description);

                    partes.push(
                        `> Matéria publicada originalmente em ${dados.site || 'outro veículo'}.`,
                        `[Ler a matéria completa](${dados.url})`
                    );

                    const markdown = partes.join('\n\n');
                    textarea.value = markdown;

                    // O editor visual ja montou por cima do textarea: sem avisar
                    // ele, a tela continuaria mostrando o campo vazio.
                    if (textarea.editorInstance) {
                        textarea.editorInstance.setMarkdown(markdown);
                    }

                    textarea.dispatchEvent(new Event('input', { bubbles: true }));
                },
            };
        }
    </script>
@endpush
