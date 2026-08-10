@extends('layouts.company')

@section('title', 'Visão geral')

@section('content')
<div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Olá, {{ str(auth()->user()->name)->before(' ') }}</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Aqui você mantém os dados da {{ $company->name }} e a equipe que aparece no portal.
    </p>
</div>

@if (! $company->isVisible())
    <div class="flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 p-5 dark:border-warning-500/30 dark:bg-warning-500/10">
        <x-admin.icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 text-warning-600 dark:text-warning-400" />
        <p class="text-sm text-warning-800 dark:text-warning-200">
            A empresa ainda não está publicada no portal, então os colaboradores também não aparecem.
            Fale com a associação se isso não estiver correto.
        </p>
    </div>
@endif

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ([
        ['Colaboradores', $stats['total'], 'no total', 'users'],
        ['Publicados', $stats['publicados'], 'aparecem no portal', 'check'],
        ['Em análise', $stats['pendentes'], 'aguardando a associação', 'inbox'],
        ['Não aprovados', $stats['rejeitados'], 'precisam de ajuste', 'alert'],
    ] as [$label, $valor, $hint, $icone])
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-300">
                <x-admin.icon :name="$icone" class="h-5 w-5" />
            </span>
            <strong class="mt-3 block text-3xl font-semibold text-gray-800 dark:text-white/90">{{ $valor }}</strong>
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</span>
        </div>
    @endforeach
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <x-admin.card class="lg:col-span-2" title="Sua equipe" subtitle="Os cadastros passam por análise da associação antes de aparecer." :padding="false">
        <x-slot:actions>
            <x-admin.button :href="route('empresa.membros.create')" variant="primary" icon="plus">Novo colaborador</x-admin.button>
        </x-slot:actions>

        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($recentes as $membro)
                <div class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex min-w-0 items-center gap-3">
                        <x-admin.avatar :photo="$membro->photo_path" :initials="$membro->avatar_initials" :color="$membro->avatar_color" />
                        <div class="min-w-0">
                            <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $membro->name }}</strong>
                            <span class="block truncate text-xs text-gray-500 dark:text-gray-400">{{ $membro->role ?: 'Cargo não informado' }}</span>
                        </div>
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <x-admin.status-badge :status="$membro->status" :published="$membro->is_active" />
                        <x-admin.button :href="route('empresa.membros.edit', $membro->id)" icon="edit">Editar</x-admin.button>
                    </div>
                </div>
            @empty
                <x-admin.empty-state icon="users" title="Nenhum colaborador cadastrado."
                                     description="Cadastre quem representa a empresa no portal da associação.">
                    <x-slot:actions>
                        <x-admin.button :href="route('empresa.membros.create')" variant="primary" icon="plus">Cadastrar o primeiro</x-admin.button>
                    </x-slot:actions>
                </x-admin.empty-state>
            @endforelse
        </div>
    </x-admin.card>

    <x-admin.card title="Complete o perfil" subtitle="Perfis completos convertem mais.">
        @if (empty($pendencias))
            <div class="flex items-center gap-3 rounded-xl bg-success-50 p-4 dark:bg-success-500/10">
                <x-admin.icon name="check" class="h-5 w-5 text-success-600 dark:text-success-400" />
                <span class="text-sm font-medium text-success-800 dark:text-success-200">Tudo preenchido.</span>
            </div>
        @else
            <ul class="space-y-3">
                @foreach ($pendencias as $item)
                    <li class="flex items-center gap-2 text-sm">
                        <span class="grid h-5 w-5 shrink-0 place-items-center rounded-full bg-warning-100 text-warning-700 dark:bg-warning-500/20 dark:text-warning-300">
                            <x-admin.icon name="x" class="h-3 w-3" />
                        </span>
                        <span class="text-gray-600 dark:text-gray-300">{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
            <x-admin.button :href="route('empresa.perfil.edit')" variant="primary" icon="edit" class="mt-5 w-full">
                Completar agora
            </x-admin.button>
        @endif
    </x-admin.card>
</div>
@endsection
