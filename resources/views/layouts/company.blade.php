@php
    $company = auth()->user()?->company;

    $menu = [
        ['label' => 'Visão geral', 'icon' => 'dashboard', 'route' => route('empresa.dashboard'), 'active' => request()->routeIs('empresa.dashboard')],
        ['label' => 'Dados da empresa', 'icon' => 'building', 'route' => route('empresa.perfil.edit'), 'active' => request()->routeIs('empresa.perfil.*')],
        ['label' => 'Colaboradores', 'icon' => 'users', 'route' => route('empresa.membros.index'), 'active' => request()->routeIs('empresa.membros.*')],
        ['label' => 'Vagas', 'icon' => 'file', 'route' => route('empresa.vagas.index'), 'active' => request()->routeIs('empresa.vagas.*')],
        // Só aparece para quem foi marcado como colunista no cadastro.
        ...(auth()->user()?->columnist()
            ? [['label' => 'Minhas colunas', 'icon' => 'news', 'route' => route('empresa.colunas.index'), 'active' => request()->routeIs('empresa.colunas.*')]]
            : []),
        // Só para a empresa delegada pela diretoria a moderar as colunas.
        ...($company?->moderates_columns
            ? [['label' => 'Moderar colunas', 'icon' => 'shield', 'route' => route('empresa.moderacao.index'), 'active' => request()->routeIs('empresa.moderacao.*'), 'badge' => \App\Models\Post::columns()->pending()->count()]]
            : []),
        ['label' => 'Reuniões', 'icon' => 'calendar', 'route' => route('empresa.reunioes.index'), 'active' => request()->routeIs('empresa.reunioes.*')],
        ['label' => 'Atas de reunião', 'icon' => 'file', 'route' => route('empresa.atas.index'), 'active' => request()->routeIs('empresa.atas.*')],
        ['label' => 'Meu acesso', 'icon' => 'shield', 'route' => route('empresa.conta.edit'), 'active' => request()->routeIs('empresa.conta.*')],
    ];
@endphp
<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Endereco que recebe a imagem colocada dentro do texto pelo editor. --}}
    <meta name="editor-upload-url" content="{{ route('editor.imagem') }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Painel') | {{ $company?->name ?? 'Empresa' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        (() => {
            if ((localStorage.getItem('theme') || 'light') === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();

        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                theme: localStorage.getItem('theme') || 'light',
                get isDark() { return this.theme === 'dark'; },
                toggle() {
                    this.theme = this.isDark ? 'light' : 'dark';
                    localStorage.setItem('theme', this.theme);
                    document.documentElement.classList.toggle('dark', this.isDark);
                },
            });
            Alpine.store('nav', { open: false });
        });
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body x-data class="bg-gray-50 font-outfit text-gray-800 dark:bg-gray-900 dark:text-gray-200">
    <header class="sticky top-0 z-9999 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
        <div class="mx-auto flex max-w-(--breakpoint-xl) items-center justify-between gap-4 px-4 py-3">
            {{--
                Duas identidades: a do portal (logo da ATPG) e a da empresa que
                está logada. Separadas por um divisor para não parecerem a
                mesma marca.
            --}}
            <div class="flex min-w-0 items-center gap-3">
                <a href="{{ route('home') }}" class="shrink-0" title="Ir para o portal">
                    <x-brand-logo class="h-9 w-auto dark:hidden" />
                    <x-brand-logo tone="white" class="hidden h-9 w-auto dark:block" />
                </a>

                <span class="hidden h-8 w-px shrink-0 bg-gray-200 dark:bg-gray-700 sm:block"></span>

                <a href="{{ route('empresa.dashboard') }}" class="flex min-w-0 items-center gap-2.5">
                    <x-admin.avatar :photo="$company?->logo_path" :initials="$company?->display_initials ?? '?'"
                                    size="h-9 w-9" contain />
                    <span class="min-w-0 leading-tight">
                        <strong class="block truncate text-sm font-semibold text-gray-900 dark:text-white">{{ $company?->name }}</strong>
                        <small class="text-xs text-gray-500 dark:text-gray-400">Painel da empresa</small>
                    </span>
                </a>
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="$store.theme.toggle()"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400">
                    <x-admin.icon name="sun" class="hidden h-5 w-5 dark:block" />
                    <x-admin.icon name="moon" class="h-5 w-5 dark:hidden" />
                    <span class="sr-only">Alternar tema</span>
                </button>

                @if ($company?->isVisible())
                    <a href="{{ route('companies.show', $company) }}" target="_blank" rel="noopener"
                       class="hidden h-10 items-center gap-2 rounded-lg border border-gray-200 px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 sm:inline-flex">
                        <x-admin.icon name="globe" class="h-4 w-4" />Ver no portal
                    </a>
                @endif

                <form method="post" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="flex h-10 items-center gap-2 rounded-lg border border-gray-200 px-3 text-sm font-medium text-error-600 hover:bg-error-50 dark:border-gray-800 dark:text-error-400">
                        <x-admin.icon name="logout" class="h-4 w-4" /><span class="hidden sm:inline">Sair</span>
                    </button>
                </form>
            </div>
        </div>

        <nav class="mx-auto max-w-(--breakpoint-xl) overflow-x-auto px-4 no-scrollbar">
            <ul class="flex gap-1 pb-1">
                @foreach ($menu as $item)
                    <li>
                        <a href="{{ $item['route'] }}"
                           @class([
                               'flex items-center gap-2 whitespace-nowrap rounded-t-lg border-b-2 px-4 py-3 text-sm font-medium transition',
                               'border-brand-500 text-brand-600 dark:text-brand-300' => $item['active'],
                               'border-transparent text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200' => ! $item['active'],
                           ])>
                            <x-admin.icon :name="$item['icon']" class="h-4 w-4" />{{ $item['label'] }}
                            {{-- Contagem só aparece quando há o que resolver. --}}
                            @if (($item['badge'] ?? 0) > 0)
                                <span class="ml-1 rounded-full bg-warning-500 px-1.5 py-0.5 text-[10px] font-bold leading-none text-white">{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
    </header>

    <main class="mx-auto max-w-(--breakpoint-xl) space-y-6 p-4 md:p-6">
        <x-admin.flash />
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
