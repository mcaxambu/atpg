@extends('layouts.admin')

@section('title', 'Reuniões')
@section('page_heading', 'Reuniões')

@section('content')
@if ($vencidos > 0)
    <a href="{{ route('admin.reunioes.index') }}#encaminhamentos"
       class="flex items-center gap-4 rounded-2xl border border-error-200 bg-error-50 p-5 dark:border-error-500/25 dark:bg-error-500/10">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-error-500 text-white">
            <x-admin.icon name="alert" class="h-6 w-6" />
        </span>
        <div>
            <strong class="block text-2xl font-semibold text-error-800 dark:text-error-200">{{ $vencidos }}</strong>
            <span class="text-sm font-medium text-error-700 dark:text-error-300">
                {{ $vencidos === 1 ? 'encaminhamento vencido' : 'encaminhamentos vencidos' }} — abra a reunião para tratar
            </span>
        </div>
    </a>
@endif

<x-admin.card :padding="false">
    <x-slot:title>{{ $total }} {{ $total === 1 ? 'reunião' : 'reuniões' }}</x-slot:title>
    <x-slot:subtitle>{{ $proximas }} agendada(s). Convocação, pauta, presença e encaminhamentos.</x-slot:subtitle>
    <x-slot:actions>
        <x-admin.button :href="route('admin.reunioes.create')" variant="primary" icon="plus">Nova reunião</x-admin.button>
    </x-slot:actions>

    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <x-admin.filter-bar :action="route('admin.reunioes.index')" placeholder="Buscar por título ou local...">
            <select name="status" class="rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <option value="">Todas as situações</option>
                @foreach (\App\Enums\MeetingStatus::options() as $valor => $rotulo)
                    <option value="{{ $valor }}" @selected(request('status') === $valor)>{{ $rotulo }}</option>
                @endforeach
            </select>
        </x-admin.filter-bar>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($reunioes as $reuniao)
            <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex min-w-0 gap-4">
                    <div class="grid h-14 w-14 shrink-0 place-items-center rounded-xl bg-brand-50 text-center leading-none dark:bg-brand-500/15">
                        <span>
                            <strong class="block text-lg font-bold text-brand-600 dark:text-brand-300">{{ $reuniao->scheduled_at->format('d') }}</strong>
                            <small class="text-[10px] font-semibold uppercase text-brand-500">{{ $reuniao->scheduled_at->translatedFormat('M') }}</small>
                        </span>
                    </div>
                    <div class="min-w-0">
                        <strong class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">{{ $reuniao->title }}</strong>
                        <span class="block text-xs text-gray-500 dark:text-gray-400">
                            {{ $reuniao->type->shortLabel() }} &middot; {{ $reuniao->scheduled_at->translatedFormat('d/m/Y') }}, {{ $reuniao->periodLabel() }}
                            @if ($reuniao->location) &middot; {{ $reuniao->location }} @endif
                        </span>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $reuniao->status->badgeClasses() }}">
                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $reuniao->status->label() }}
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $reuniao->confirmadas }} confirmada(s)</span>
                            @if ($reuniao->pendencias > 0)
                                <span class="text-xs font-medium text-warning-600 dark:text-warning-400">
                                    {{ $reuniao->pendencias }} encaminhamento(s) pendente(s)
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <x-admin.button :href="route('admin.reunioes.show', $reuniao)" variant="primary" icon="eye">Gerenciar</x-admin.button>
                    <x-admin.button :href="route('admin.reunioes.edit', $reuniao)" icon="edit">Editar</x-admin.button>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="calendar" title="Nenhuma reunião cadastrada."
                                 description="Crie a reunião, monte a pauta e envie o link de convocação no WhatsApp.">
                <x-slot:actions>
                    <x-admin.button :href="route('admin.reunioes.create')" variant="primary" icon="plus">Nova reunião</x-admin.button>
                </x-slot:actions>
            </x-admin.empty-state>
        @endforelse
    </div>

    @if ($reunioes->hasPages())
        <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">{{ $reunioes->links() }}</div>
    @endif
</x-admin.card>
@endsection
