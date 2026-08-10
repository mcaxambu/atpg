@extends('layouts.admin')

@section('title', $reuniao->title)
@section('page_heading', 'Painel da reunião')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $quorum = $reuniao->hasQuorum();
@endphp

<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-3">
            <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $reuniao->title }}</h2>
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $reuniao->status->badgeClasses() }}">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $reuniao->status->label() }}
            </span>
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            {{ $reuniao->type->label() }} &middot;
            {{ $reuniao->scheduled_at->translatedFormat('d \d\e F \d\e Y') }}, {{ $reuniao->periodLabel() }}
            @if ($reuniao->location) &middot; {{ $reuniao->location }} @endif
        </p>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-admin.button :href="route('admin.reunioes.index')" variant="ghost">Voltar</x-admin.button>
        <x-admin.button :href="route('admin.reunioes.edit', $reuniao)" icon="edit">Editar</x-admin.button>
        @if ($reuniao->minute)
            <x-admin.button :href="route('admin.atas.edit', $reuniao->minute)" icon="file">Ver ata</x-admin.button>
        @else
            <x-admin.confirm-form :action="route('admin.reunioes.gerar-ata', $reuniao)" method="post"
                                  variant="primary" icon="file"
                                  message="Gerar a ata a partir da pauta, presença e encaminhamentos?">Gerar ata</x-admin.confirm-form>
        @endif
    </div>
</div>

{{-- Link de convocação: é o que vai para o WhatsApp. --}}
<x-admin.card title="Link de convocação" subtitle="Envie este endereço no grupo. Não exige login.">
    <div x-data="{ copiado: false }" class="flex flex-col gap-3 sm:flex-row sm:items-center">
        <input type="text" readonly value="{{ $reuniao->publicUrl() }}"
               x-ref="link" @focus="$refs.link.select()"
               class="{{ $input }} font-mono text-xs">

        <div class="flex shrink-0 gap-2">
            <x-admin.button type="button" variant="primary" icon="check"
                            @click="navigator.clipboard.writeText($refs.link.value); copiado = true; setTimeout(() => copiado = false, 2000)">
                <span x-text="copiado ? 'Copiado!' : 'Copiar'"></span>
            </x-admin.button>
            <x-admin.button :href="$reuniao->publicUrl()" target="_blank" rel="noopener" icon="eye">Abrir</x-admin.button>
        </div>
    </div>
</x-admin.card>

<div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    @foreach ([
        ['Convocadas', $reuniao->attendances->count(), 'empresas na lista', 'building'],
        ['Confirmaram', $reuniao->confirmedCount(), 'vão participar', 'check'],
        ['Presentes', $reuniao->attendedCount(), 'no dia', 'users'],
        ['Encaminhamentos', $reuniao->actionItems->where('status', 'pendente')->count(), 'pendentes', 'inbox'],
    ] as [$rotulo, $valor, $dica, $icone])
        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
            <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-300">
                <x-admin.icon :name="$icone" class="h-5 w-5" />
            </span>
            <strong class="mt-3 block text-3xl font-semibold text-gray-800 dark:text-white/90">{{ $valor }}</strong>
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">{{ $rotulo }}</span>
            <span class="mt-1 block text-xs text-gray-500 dark:text-gray-400">{{ $dica }}</span>
        </div>
    @endforeach
</div>

@if ($quorum !== null)
    <div @class([
        'flex items-start gap-3 rounded-2xl border p-5',
        'border-success-200 bg-success-50 dark:border-success-500/30 dark:bg-success-500/10' => $quorum,
        'border-warning-200 bg-warning-50 dark:border-warning-500/30 dark:bg-warning-500/10' => ! $quorum,
    ])>
        <x-admin.icon :name="$quorum ? 'check' : 'alert'" class="mt-0.5 h-5 w-5 shrink-0" />
        <p class="text-sm">
            <strong>Quórum mínimo: {{ $reuniao->quorum_minimum }} empresas.</strong>
            {{ $quorum ? 'Atingido.' : 'Ainda não atingido.' }}
            <span class="text-gray-600 dark:text-gray-300">
                Considerando {{ $reuniao->status === \App\Enums\MeetingStatus::Realizada ? 'a presença registrada' : 'as confirmações' }}.
            </span>
        </p>
    </div>
@endif

{{-- ---------- Presença ---------- --}}
<x-admin.card title="Lista de presença" subtitle="Confirmações recebidas e presença registrada no dia." :padding="false">
    <x-slot:actions>
        @if ($ausentesDaLista->isNotEmpty())
            <x-admin.confirm-form :action="route('admin.reunioes.lista', $reuniao)" method="post"
                                  icon="plus"
                                  message="Adicionar {{ $ausentesDaLista->count() }} empresa(s) que entraram depois?">
                Atualizar lista ({{ $ausentesDaLista->count() }})
            </x-admin.confirm-form>
        @endif
    </x-slot:actions>

    <form method="post" action="{{ route('admin.reunioes.presencas', $reuniao) }}">
        @csrf
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                <thead class="bg-gray-50 dark:bg-white/[0.02]">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Empresa</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Resposta</th>
                        <th class="px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500">Respondeu</th>
                        <th class="px-5 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500">Compareceu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($reuniao->attendances->sortBy(fn ($p) => $p->company?->name) as $presenca)
                        <tr>
                            <td class="px-5 py-3 text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ $presenca->company?->name ?? '—' }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $presenca->status->badgeClasses() }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $presenca->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500 dark:text-gray-400">
                                @if ($presenca->responded_by)
                                    {{ $presenca->responded_by }}
                                    <span class="block">{{ $presenca->responded_at?->format('d/m H:i') }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <input type="checkbox" name="presentes[]" value="{{ $presenca->company_id }}"
                                       @checked($presenca->attended)
                                       class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <x-admin.empty-state icon="building" title="Nenhuma empresa na lista."
                                                     description="Publique empresas no portal para que apareçam aqui." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reuniao->attendances->isNotEmpty())
            <div class="border-t border-gray-100 px-5 py-4 dark:border-gray-800">
                <x-admin.button type="submit" variant="primary" icon="check">
                    Registrar presença e marcar como realizada
                </x-admin.button>
            </div>
        @endif
    </form>
</x-admin.card>

{{-- ---------- Pauta e resultados ---------- --}}
<x-admin.card title="Pauta e deliberações" subtitle="Registre o desfecho de cada item depois da reunião.">
    @forelse ($reuniao->agendaItems as $indice => $item)
        <div class="mb-4 rounded-xl border border-gray-200 p-4 last:mb-0 dark:border-gray-700">
            <div class="mb-3">
                <strong class="text-sm font-semibold text-gray-800 dark:text-white/90">
                    {{ $indice + 1 }}. {{ $item->title }}
                </strong>
                @if ($item->presenter)
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ $item->presenter }}</span>
                @endif
                @if ($item->description)
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $item->description }}</p>
                @endif
            </div>

            <form method="post" action="{{ route('admin.reunioes.resultado', [$reuniao, $item]) }}"
                  class="grid gap-3 sm:grid-cols-8">
                @csrf

                <select name="outcome" class="{{ $input }} sm:col-span-2">
                    <option value="">Sem desfecho</option>
                    @foreach (\App\Models\MeetingAgendaItem::OUTCOMES as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected($item->outcome === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>

                <input type="number" name="votes_for" min="0" value="{{ $item->votes_for }}"
                       placeholder="A favor" class="{{ $input }}">
                <input type="number" name="votes_against" min="0" value="{{ $item->votes_against }}"
                       placeholder="Contra" class="{{ $input }}">
                <input type="number" name="votes_abstain" min="0" value="{{ $item->votes_abstain }}"
                       placeholder="Abstenções" class="{{ $input }}">

                <input type="text" name="outcome_notes" value="{{ $item->outcome_notes }}"
                       placeholder="Observações da deliberação" class="{{ $input }} sm:col-span-2">

                <x-admin.button type="submit" icon="check" class="sm:col-span-8 sm:w-fit">Salvar item</x-admin.button>
            </form>
        </div>
    @empty
        <x-admin.empty-state icon="file" title="Pauta vazia."
                             description="Edite a reunião para adicionar os itens da pauta.">
            <x-slot:actions>
                <x-admin.button :href="route('admin.reunioes.edit', $reuniao)" variant="primary" icon="edit">Montar pauta</x-admin.button>
            </x-slot:actions>
        </x-admin.empty-state>
    @endforelse
</x-admin.card>

{{-- ---------- Encaminhamentos ---------- --}}
<x-admin.card id="encaminhamentos" title="Encaminhamentos"
              subtitle="O que ficou decidido, com responsável e prazo." :padding="false">
    <div class="border-b border-gray-100 p-5 dark:border-gray-800">
        <form method="post" action="{{ route('admin.reunioes.encaminhamentos.store', $reuniao) }}"
              class="grid gap-3 sm:grid-cols-8">
            @csrf
            <input type="text" name="title" required placeholder="O que precisa ser feito"
                   class="{{ $input }} sm:col-span-3">
            <input type="text" name="responsible" placeholder="Responsável" class="{{ $input }} sm:col-span-2">
            <input type="date" name="due_date" class="{{ $input }}">
            <select name="meeting_agenda_item_id" class="{{ $input }}">
                <option value="">Item da pauta</option>
                @foreach ($reuniao->agendaItems as $item)
                    <option value="{{ $item->id }}">{{ Str::limit($item->title, 30) }}</option>
                @endforeach
            </select>
            <x-admin.button type="submit" variant="primary" icon="plus">Adicionar</x-admin.button>
        </form>
    </div>

    <div class="divide-y divide-gray-100 dark:divide-gray-800">
        @forelse ($reuniao->actionItems as $encaminhamento)
            <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <strong @class([
                        'block text-sm font-semibold',
                        'text-gray-400 line-through' => $encaminhamento->status === 'concluido',
                        'text-gray-800 dark:text-white/90' => $encaminhamento->status !== 'concluido',
                    ])>{{ $encaminhamento->title }}</strong>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">
                        {{ $encaminhamento->responsible ?: 'Sem responsável' }}
                        @if ($encaminhamento->due_date)
                            &middot; prazo {{ $encaminhamento->due_date->format('d/m/Y') }}
                        @endif
                        @if ($encaminhamento->agendaItem)
                            &middot; {{ Str::limit($encaminhamento->agendaItem->title, 40) }}
                        @endif
                    </span>
                    @if ($encaminhamento->isOverdue())
                        <span class="mt-1 inline-flex items-center gap-1.5 rounded-full bg-error-50 px-2.5 py-1 text-xs font-semibold text-error-700 dark:bg-error-500/15 dark:text-error-300">
                            <x-admin.icon name="alert" class="h-3 w-3" />Vencido
                        </span>
                    @endif
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <form method="post" action="{{ route('admin.encaminhamentos.update', $encaminhamento) }}">
                        @csrf
                        @method('patch')
                        <select name="status" onchange="this.form.submit()"
                                class="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            @foreach (\App\Models\MeetingActionItem::STATUSES as $valor => $rotulo)
                                <option value="{{ $valor }}" @selected($encaminhamento->status === $valor)>{{ $rotulo }}</option>
                            @endforeach
                        </select>
                    </form>
                    <x-admin.confirm-form :action="route('admin.encaminhamentos.destroy', $encaminhamento)"
                                          message="Remover este encaminhamento?">Remover</x-admin.confirm-form>
                </div>
            </div>
        @empty
            <x-admin.empty-state icon="inbox" title="Nenhum encaminhamento."
                                 description="Registre aqui o que ficou decidido, com responsável e prazo." />
        @endforelse
    </div>
</x-admin.card>
@endsection
