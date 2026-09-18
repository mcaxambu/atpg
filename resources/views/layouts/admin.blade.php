@php
    use App\Http\Controllers\Admin\CmsModuleController;

    $pendingCompanies = \App\Models\Company::pending()->count();
    $pendingMembers = \App\Models\Member::pending()->count();
    $pendingJobs = \App\Models\JobOpening::pending()->count();
    $pendingColumns = \App\Models\Post::columns()->pending()->count();

    $isCms = request()->routeIs('admin.cms.*');

    $mainMenu = [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
        ['label' => 'Membros', 'module' => 'members', 'icon' => 'users', 'route' => route('admin.members.index'), 'active' => request()->routeIs('admin.members.index') || request()->routeIs('admin.members.create') || request()->routeIs('admin.members.edit') || request()->routeIs('admin.members.show')],
        ['label' => 'Membros pendentes', 'module' => 'members', 'icon' => 'inbox', 'route' => route('admin.members.pending'), 'active' => request()->routeIs('admin.members.pending'), 'badge' => $pendingMembers],
        ['label' => 'Empresas', 'module' => 'companies', 'icon' => 'building', 'route' => route('admin.companies.index'), 'active' => request()->routeIs('admin.companies.index') || request()->routeIs('admin.companies.create') || request()->routeIs('admin.companies.edit') || request()->routeIs('admin.companies.show')],
        ['label' => 'Empresas pendentes', 'module' => 'companies', 'icon' => 'inbox', 'route' => route('admin.companies.pending'), 'active' => request()->routeIs('admin.companies.pending'), 'badge' => $pendingCompanies],
        ['label' => 'Vagas', 'module' => 'jobs', 'icon' => 'file', 'route' => route('admin.jobs.index'), 'active' => request()->routeIs('admin.jobs.index') || request()->routeIs('admin.jobs.show')],
        ['label' => 'Vagas pendentes', 'module' => 'jobs', 'icon' => 'inbox', 'route' => route('admin.jobs.pending'), 'active' => request()->routeIs('admin.jobs.pending'), 'badge' => $pendingJobs],
        ['label' => 'Colunas', 'module' => 'columns', 'icon' => 'news', 'route' => route('admin.columns.index'), 'active' => request()->routeIs('admin.columns.index') || request()->routeIs('admin.columns.show') || request()->routeIs('admin.columns.columnists')],
        ['label' => 'Colunas pendentes', 'module' => 'columns', 'icon' => 'inbox', 'route' => route('admin.columns.pending'), 'active' => request()->routeIs('admin.columns.pending'), 'badge' => $pendingColumns],
        ['label' => 'Reuniões', 'module' => 'meetings', 'icon' => 'calendar', 'route' => route('admin.reunioes.index'), 'active' => request()->routeIs('admin.reunioes.*')],
        ['label' => 'Especialidades', 'module' => 'specialties', 'icon' => 'tag', 'route' => route('admin.specialties.index'), 'active' => request()->routeIs('admin.specialties.*')],
        ['label' => 'Atas de reunião', 'module' => 'minutes', 'icon' => 'file', 'route' => route('admin.atas.index'), 'active' => request()->routeIs('admin.atas.*')],
    ];

    $moduleIcons = [
        'categorias' => 'layers',
        'banners' => 'image',
        'destaques' => 'star',
        'depoimentos' => 'quote',
        'projetos' => 'layers',
        'parceiros' => 'handshake',
        'missao-visao' => 'star',
        'valores' => 'layers',
    ];

    $cmsMenu = [
        ['label' => 'Visão geral', 'icon' => 'dashboard', 'route' => route('admin.cms.dashboard'), 'active' => request()->routeIs('admin.cms.dashboard')],
        ['label' => 'Notícias', 'icon' => 'news', 'route' => route('admin.cms.posts.index'), 'active' => request()->routeIs('admin.cms.posts.*')],
        ['label' => 'Eventos', 'icon' => 'calendar', 'route' => route('admin.cms.events.index'), 'active' => request()->routeIs('admin.cms.events.*')],
        ['label' => 'Páginas', 'icon' => 'file', 'route' => route('admin.cms.pages.index'), 'active' => request()->routeIs('admin.cms.pages.*')],
    ];

    foreach (CmsModuleController::modules() as $slug => $meta) {
        $cmsMenu[] = [
            'label' => $meta['title'],
            'icon' => $moduleIcons[$slug] ?? 'layers',
            'route' => route('admin.cms.modules.index', $slug),
            'active' => request()->is("*/cms/modulos/{$slug}/*"),
        ];
    }

    $cmsMenu[] = ['label' => 'Configurações', 'icon' => 'settings', 'route' => route('admin.cms.settings.edit'), 'active' => request()->routeIs('admin.cms.settings.*')];

    $systemMenu = [
        ['label' => 'Usuários', 'module' => 'users', 'icon' => 'shield', 'route' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')],
        ['label' => 'Lixeira de empresas', 'module' => 'companies', 'icon' => 'trash', 'route' => route('admin.companies.trash'), 'active' => request()->routeIs('admin.companies.trash')],
        ['label' => 'Lixeira de membros', 'module' => 'members', 'icon' => 'trash', 'route' => route('admin.members.trash'), 'active' => request()->routeIs('admin.members.trash')],
        ['label' => 'Ver site público', 'icon' => 'globe', 'route' => route('home'), 'active' => false],
    ];

    $currentUser = auth()->user();

    // Item sem 'module' e livre para todo admin (dashboard, site publico).
    $allowed = fn (array $item) => ! isset($item['module']) || $currentUser?->canAccessModule($item['module']);

    $mainMenu = array_values(array_filter($mainMenu, $allowed));
    $systemMenu = array_values(array_filter($systemMenu, $allowed));

    $canUseCms = (bool) $currentUser?->canAccessModule('cms');

    if (! $canUseCms) {
        $cmsMenu = [];
        $isCms = false;
    }
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
    <title>@yield('title', 'Painel') | Associação Tech PG</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Aplica o tema antes da primeira pintura para nao piscar branco.
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

            Alpine.store('sidebar', {
                isExpanded: window.innerWidth >= 1280,
                isMobileOpen: false,
                isHovered: false,
                get isWide() { return this.isExpanded || this.isHovered || this.isMobileOpen; },
                toggleExpanded() { this.isExpanded = !this.isExpanded; this.isMobileOpen = false; },
                toggleMobileOpen() { this.isMobileOpen = !this.isMobileOpen; },
                setMobileOpen(value) { this.isMobileOpen = value; },
                setHovered(value) { if (window.innerWidth >= 1280 && !this.isExpanded) this.isHovered = value; },
            });
        });
    </script>
    <style>[x-cloak] { display: none !important; }</style>
</head>
{{--
    O x-data no body é obrigatório: sem um escopo Alpine ancestral, o framework
    ignora x-show/:class/@click do layout inteiro — os rótulos ficam presos no
    x-cloak e o conteúdo perde a margem, ficando embaixo da sidebar fixa.
--}}
<body x-data class="bg-gray-50 font-outfit text-gray-800 dark:bg-gray-900 dark:text-gray-200">
    {{--
        A largura da sidebar vive numa variável CSS com o padrão já correto.
        Assim o layout renderiza certo mesmo antes do Alpine subir (ou se ele
        falhar), e não há classes de margem concorrentes brigando por ordem.
    --}}
    <div class="min-h-screen xl:flex" style="--admin-sidebar-w: 290px"
         :style="'--admin-sidebar-w: ' + ($store.sidebar.isWide ? '290px' : '90px')">
        <div x-show="$store.sidebar.isMobileOpen" x-cloak @click="$store.sidebar.setMobileOpen(false)"
             class="fixed inset-0 z-50 bg-gray-900/50 xl:hidden"></div>

        <aside
            class="fixed left-0 top-0 z-99999 flex h-screen w-[290px] flex-col overflow-hidden border-r border-gray-200 bg-white px-4 transition-all duration-300 ease-in-out dark:border-gray-800 dark:bg-gray-900 xl:w-(--admin-sidebar-w) xl:translate-x-0"
            :class="$store.sidebar.isMobileOpen ? 'translate-x-0' : '-translate-x-full'"
            @mouseenter="$store.sidebar.setHovered(true)"
            @mouseleave="$store.sidebar.setHovered(false)"
        >
            {{--
                Expandida mostra a logo completa; recolhida (90px) so o símbolo,
                onde a horizontal não caberia. A placa branca é necessária no
                tema escuro: o texto da logo é azul-escuro.
            --}}
            <a href="{{ route('admin.dashboard') }}" class="flex items-center py-6"
               :class="$store.sidebar.isWide ? 'justify-start px-1' : 'xl:justify-center'">
                <span x-show="$store.sidebar.isWide" class="block">
                    <x-brand-logo class="h-11 w-auto dark:hidden" />
                    <x-brand-logo tone="white" class="hidden h-11 w-auto dark:block" />
                </span>
                <span x-show="!$store.sidebar.isWide" x-cloak class="hidden xl:block">
                    <x-brand-logo variant="symbol" class="h-10 w-10 dark:hidden" />
                    <x-brand-logo variant="symbol" tone="white" class="hidden h-10 w-10 dark:block" />
                </span>
            </a>

            <nav class="flex flex-1 flex-col gap-6 overflow-y-auto pb-6 no-scrollbar">
                @foreach ([['Gestão', $mainMenu], ['CMS do website', $cmsMenu], ['Sistema', $systemMenu]] as [$groupTitle, $items])
                    @continue (empty($items))
                    <div>
                        <h2 class="mb-3 flex items-center px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-400"
                            :class="$store.sidebar.isWide ? 'justify-start' : 'xl:justify-center'">
                            <span x-show="$store.sidebar.isWide">{{ $groupTitle }}</span>
                            <span x-show="!$store.sidebar.isWide" x-cloak>&middot;&middot;&middot;</span>
                        </h2>

                        <ul class="flex flex-col gap-1">
                            @foreach ($items as $item)
                                <li>
                                    <a href="{{ $item['route'] }}"
                                       @class([
                                           'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                                           'bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300' => $item['active'],
                                           'text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/[0.04]' => ! $item['active'],
                                       ])
                                       :class="$store.sidebar.isWide ? '' : 'xl:justify-center'"
                                       title="{{ $item['label'] }}">
                                        <x-admin.icon :name="$item['icon']" class="h-5 w-5 shrink-0" />
                                        <span x-show="$store.sidebar.isWide" class="flex flex-1 items-center justify-between gap-2">
                                            {{ $item['label'] }}
                                            @if (($item['badge'] ?? 0) > 0)
                                                <span class="rounded-full bg-warning-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $item['badge'] }}</span>
                                            @endif
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </nav>
        </aside>

        <div class="min-w-0 flex-1 transition-all duration-300 ease-in-out xl:ml-(--admin-sidebar-w)">
            <header class="sticky top-0 z-9999 border-b border-gray-200 bg-white/95 backdrop-blur dark:border-gray-800 dark:bg-gray-900/95">
                <div class="flex items-center justify-between gap-4 px-4 py-3 sm:px-6">
                    <div class="flex min-w-0 items-center gap-3">
                        <button type="button" @click="$store.sidebar.toggleExpanded()"
                                class="hidden h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.04] xl:flex">
                            <x-admin.icon name="menu" />
                            <span class="sr-only">Recolher menu</span>
                        </button>
                        <button type="button" @click="$store.sidebar.toggleMobileOpen()"
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 text-gray-500 dark:border-gray-800 dark:text-gray-400 xl:hidden">
                            <x-admin.icon name="menu" />
                            <span class="sr-only">Abrir menu</span>
                        </button>
                        <div class="min-w-0">
                            <span class="block text-[11px] font-semibold uppercase tracking-wider text-brand-500">Associação Tech PG</span>
                            <h1 class="truncate text-lg font-semibold text-gray-800 dark:text-white/90">@yield('page_heading', 'Painel administrativo')</h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="$store.theme.toggle()"
                                class="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.04]">
                            <x-admin.icon name="sun" class="hidden h-5 w-5 dark:block" />
                            <x-admin.icon name="moon" class="h-5 w-5 dark:hidden" />
                            <span class="sr-only">Alternar tema</span>
                        </button>

                        <a href="{{ route('home') }}" target="_blank" rel="noopener"
                           class="hidden h-10 items-center gap-2 rounded-lg border border-gray-200 px-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.04] sm:inline-flex">
                            <x-admin.icon name="globe" class="h-4 w-4" />Ver site
                        </a>

                        <div x-data="{ open: false }" class="relative">
                            <button type="button" @click="open = !open" class="flex items-center rounded-full">
                                <span class="grid h-10 w-10 place-items-center rounded-full bg-brand-500 text-sm font-semibold text-white">
                                    {{ str($currentUser?->name ?? 'A')->substr(0, 1)->upper() }}
                                </span>
                                <span class="sr-only">Abrir menu do usuário</span>
                            </button>

                            <div x-show="open" x-cloak @click.outside="open = false" x-transition
                                 class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-theme-lg dark:border-gray-800 dark:bg-gray-900">
                                <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                                    <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $currentUser?->name }}</strong>
                                    <small class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $currentUser?->email }}</small>
                                </div>
                                <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.04]">
                                    <x-admin.icon name="users" class="h-4 w-4" />Meu perfil
                                </a>
                                @if ($currentUser?->canAccessModule('users'))
                                    <a href="{{ route('admin.users.index') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/[0.04]">
                                        <x-admin.icon name="shield" class="h-4 w-4" />Usuários
                                    </a>
                                @endif
                                <form method="post" action="{{ route('admin.logout') }}" class="border-t border-gray-100 dark:border-gray-800">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-left text-sm text-error-600 hover:bg-error-50 dark:text-error-400 dark:hover:bg-error-500/10">
                                        <x-admin.icon name="logout" class="h-4 w-4" />Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <main class="mx-auto max-w-(--breakpoint-2xl) space-y-6 p-4 md:p-6">
                <x-admin.flash />
                @yield('content')
            </main>
        </div>
    </div>
    @stack('scripts')
</body>
</html>
