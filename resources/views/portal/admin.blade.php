@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_heading', 'Painel administrativo')

@section('content')
@php
    // O dashboard so mostra o que o usuario consegue abrir: card que leva a 403
    // e pior do que card ausente.
    $can = fn (string $module) => auth()->user()?->canAccessModule($module);

    $cards = collect([
        ['module' => 'members', 'label' => 'Membros', 'value' => $stats['members'], 'hint' => $stats['membersPublished'].' publicados', 'icon' => 'users', 'route' => route('admin.members.index')],
        ['module' => 'companies', 'label' => 'Empresas', 'value' => $stats['companies'], 'hint' => $stats['companiesPublished'].' publicadas', 'icon' => 'building', 'route' => route('admin.companies.index')],
        ['module' => 'specialties', 'label' => 'Especialidades', 'value' => $stats['specialties'], 'hint' => 'áreas de atuação', 'icon' => 'tag', 'route' => route('admin.specialties.index')],
        ['module' => 'cms', 'label' => 'Notícias', 'value' => $stats['posts'], 'hint' => $stats['postsPublished'].' publicadas', 'icon' => 'news', 'route' => route('admin.cms.posts.index')],
        ['module' => 'cms', 'label' => 'Eventos', 'value' => $stats['events'], 'hint' => $stats['eventsUpcoming'].' próximos', 'icon' => 'calendar', 'route' => route('admin.cms.events.index')],
        ['module' => 'cms', 'label' => 'Páginas', 'value' => $stats['pages'], 'hint' => 'no CMS', 'icon' => 'file', 'route' => route('admin.cms.pages.index')],
    ])->filter(fn ($card) => $can($card['module']))->values()->all();

    $canCompanies = $can('companies');
    $canMembers = $can('members');
    $canCms = $can('cms');

    // Contagem pendente idem: so conta o que ele pode resolver.
    $totalPending = ($canCompanies ? $stats['companiesPending'] : 0)
        + ($canMembers ? $stats['membersPending'] : 0);
@endphp

<div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Olá, {{ str(auth()->user()->name)->before(' ') }}</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            @if ($totalPending > 0)
                Você tem <strong class="text-warning-600 dark:text-warning-400">{{ $totalPending }}</strong>
                {{ $totalPending === 1 ? 'cadastro aguardando' : 'cadastros aguardando' }} análise.
            @else
                Nenhum cadastro aguardando análise. Tudo em dia.
            @endif
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if ($canMembers)
            <x-admin.button :href="route('admin.members.create')" icon="plus">Novo membro</x-admin.button>
        @endif
        @if ($canCms)
            <x-admin.button :href="route('admin.cms.posts.create')" icon="news">Publicar notícia</x-admin.button>
        @endif
        @if ($canCompanies)
            <x-admin.button :href="route('admin.companies.create')" variant="primary" icon="plus">Nova empresa</x-admin.button>
        @endif
    </div>
</div>

{{-- Filas de aprovação primeiro: o que exige ação vem antes das métricas. --}}
@if ($totalPending > 0)
    <div class="grid gap-4 sm:grid-cols-2">
        @foreach (array_filter([
            $canCompanies ? ['Empresas pendentes', $stats['companiesPending'], route('admin.companies.pending'), 'building'] : null,
            $canMembers ? ['Membros pendentes', $stats['membersPending'], route('admin.members.pending'), 'users'] : null,
        ]) as [$label, $count, $url, $icon])
            @if ($count > 0)
                <a href="{{ $url }}" class="flex items-center gap-4 rounded-2xl border border-warning-200 bg-warning-50 p-5 transition hover:-translate-y-0.5 hover:shadow-theme-md dark:border-warning-500/25 dark:bg-warning-500/10">
                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-warning-500 text-white">
                        <x-admin.icon :name="$icon" class="h-6 w-6" />
                    </span>
                    <div>
                        <strong class="block text-2xl font-semibold text-warning-800 dark:text-warning-200">{{ $count }}</strong>
                        <span class="text-sm font-medium text-warning-700 dark:text-warning-300">{{ $label }} &rarr; analisar agora</span>
                    </div>
                </a>
            @endif
        @endforeach
    </div>
@endif

<div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
    @foreach ($cards as $card)
        <a href="{{ $card['route'] }}" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm transition hover:-translate-y-0.5 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03]">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-300">
                <x-admin.icon :name="$card['icon']" class="h-5 w-5" />
            </span>
            <strong class="mt-3 block text-3xl font-semibold text-gray-800 dark:text-white/90">{{ $card['value'] }}</strong>
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $card['label'] }}</span>
            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $card['hint'] }}</span>
        </a>
    @endforeach
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
    @if ($canCompanies)
    <x-admin.card title="Empresas aguardando análise" subtitle="Cadastros recebidos pelo formulário público." :padding="false">
        <x-slot:actions>
            <x-admin.button :href="route('admin.companies.pending')" variant="ghost">Ver fila</x-admin.button>
        </x-slot:actions>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($pendingCompanies as $company)
                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <x-admin.avatar :photo="$company->logo_path" :initials="$company->display_initials" contain />
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $company->name }}</strong>
                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $company->contact_name ?: 'Responsável não informado' }}@if($company->email) &middot; {{ $company->email }}@endif
                            </span>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <x-admin.button :href="route('admin.companies.show', $company)" icon="eye">Analisar</x-admin.button>
                        <x-admin.confirm-form :action="route('admin.companies.approve', $company)" method="patch"
                                              variant="success" icon="check"
                                              message="Aprovar e publicar {{ $company->name }} no portal?">Aprovar</x-admin.confirm-form>
                    </div>
                </div>
            @empty
                <x-admin.empty-state icon="check" title="Nenhuma empresa pendente."
                                     description="Os novos cadastros públicos aparecem aqui automaticamente." />
            @endforelse
        </div>
    </x-admin.card>
    @endif

    @if ($canMembers)
    <x-admin.card title="Membros aguardando análise" subtitle="Profissionais que se cadastraram pelo portal." :padding="false">
        <x-slot:actions>
            <x-admin.button :href="route('admin.members.pending')" variant="ghost">Ver fila</x-admin.button>
        </x-slot:actions>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($pendingMembers as $member)
                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <x-admin.avatar :photo="$member->photo_path" :initials="$member->avatar_initials" :color="$member->avatar_color" />
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $member->name }}</strong>
                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $member->role ?: 'Cargo não informado' }} &middot; {{ $member->company?->name ?? 'Profissional independente' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-2">
                        <x-admin.button :href="route('admin.members.show', $member)" icon="eye">Analisar</x-admin.button>
                        <x-admin.confirm-form :action="route('admin.members.approve', $member)" method="patch"
                                              variant="success" icon="check"
                                              message="Aprovar e publicar {{ $member->name }} no portal?">Aprovar</x-admin.confirm-form>
                    </div>
                </div>
            @empty
                <x-admin.empty-state icon="check" title="Nenhum membro pendente."
                                     description="Os cadastros feitos pelo portal chegam nesta fila." />
            @endforelse
        </div>
    </x-admin.card>
    @endif
</div>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    @if ($canMembers)
    <x-admin.card class="xl:col-span-2" title="Membros cadastrados recentemente" :padding="false">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Membro</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Empresa</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Situação</th>
                        <th class="px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($recentMembers as $member)
                        <tr>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <x-admin.avatar :photo="$member->photo_path" :initials="$member->avatar_initials" :color="$member->avatar_color" size="h-10 w-10" />
                                    <div>
                                        <strong class="block text-sm font-semibold text-gray-800 dark:text-white/90">{{ $member->name }}</strong>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $member->specialties->pluck('name')->join(', ') ?: 'Sem especialidade' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $member->company?->name ?? 'Independente' }}</td>
                            <td class="px-5 py-4"><x-admin.status-badge :status="$member->status" :published="$member->is_active" /></td>
                            <td class="px-5 py-4 text-right">
                                <x-admin.button :href="route('admin.members.edit', $member)" icon="edit">Editar</x-admin.button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><x-admin.empty-state icon="users" title="Nenhum membro cadastrado ainda." /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-admin.card>
    @endif

    @if ($canCms)
    <x-admin.card title="Próximos eventos" :padding="false">
        <x-slot:actions>
            <x-admin.button :href="route('admin.cms.events.create')" variant="ghost" icon="plus">Novo</x-admin.button>
        </x-slot:actions>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($upcomingEvents as $event)
                <a href="{{ route('admin.cms.events.edit', $event) }}" class="flex items-center gap-3 px-5 py-4 hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-brand-50 text-center leading-none dark:bg-brand-500/15">
                        <span>
                            <strong class="block text-base font-bold text-brand-600 dark:text-brand-300">{{ $event->event_date->format('d') }}</strong>
                            <small class="text-[10px] font-semibold uppercase text-brand-500">{{ $event->event_date->translatedFormat('M') }}</small>
                        </span>
                    </div>
                    <div class="min-w-0">
                        <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $event->title }}</strong>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $event->location ?: 'Local a definir' }}</span>
                    </div>
                </a>
            @empty
                <x-admin.empty-state icon="calendar" title="Nenhum evento agendado."
                                     description="Cadastre um evento para ele aparecer no portal.">
                    <x-slot:actions>
                        <x-admin.button :href="route('admin.cms.events.create')" variant="primary" icon="plus">Criar evento</x-admin.button>
                    </x-slot:actions>
                </x-admin.empty-state>
            @endforelse
        </div>
    </x-admin.card>
    @endif
</div>
@endsection
