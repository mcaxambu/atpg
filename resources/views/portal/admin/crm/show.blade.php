@extends('layouts.admin')

@section('title', $prospect->name)
@section('page_heading', $prospect->name)

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
@endphp

<div class="grid gap-6 xl:grid-cols-3">
    <div class="space-y-6 xl:col-span-2">
        {{--
            Registrar contato é a ação mais frequente desta tela, então fica no
            topo e já grava o próximo passo junto: "liguei, retorno terça" são
            duas informações que nascem na mesma frase.
        --}}
        <x-admin.card title="Registrar contato">
            <form method="post" action="{{ route('admin.crm.interactions.store', $prospect) }}" class="space-y-4">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="Como foi o contato" name="type" required>
                        <select name="type" class="{{ $input }}">
                            @foreach ($tiposDeContato as $tipo)
                                <option value="{{ $tipo->value }}" @selected(old('type') === $tipo->value)>{{ $tipo->label() }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>

                    <x-admin.field label="Quando" name="happened_at" hint="Em branco, agora.">
                        <input type="datetime-local" name="happened_at" value="{{ old('happened_at') }}" class="{{ $input }}">
                    </x-admin.field>
                </div>

                <x-admin.field label="O que foi conversado" name="summary" required>
                    <textarea name="summary" rows="3" required class="{{ $input }}">{{ old('summary') }}</textarea>
                </x-admin.field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="Próximo passo" name="next_action">
                        <input type="text" name="next_action" value="{{ old('next_action') }}"
                               placeholder="{{ $prospect->next_action ?: 'Ligar de novo' }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Data do próximo passo" name="next_action_at">
                        <input type="date" name="next_action_at" value="{{ old('next_action_at') }}" class="{{ $input }}">
                    </x-admin.field>
                </div>

                <x-admin.button type="submit" variant="primary" icon="check">Registrar</x-admin.button>
            </form>
        </x-admin.card>

        <x-admin.card :padding="false">
            <x-slot:title>Histórico</x-slot:title>
            <x-slot:subtitle>Tudo que já aconteceu com este prospecto, do mais recente para o mais antigo.</x-slot:subtitle>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($prospect->interactions as $contato)
                    <div class="flex gap-3 p-4 {{ $contato->isSistema() ? 'bg-gray-50/60 dark:bg-white/[0.02]' : '' }}">
                        <x-admin.icon :name="$contato->type->icon()" class="mt-0.5 h-4 w-4 shrink-0 text-gray-400" />

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-x-2 text-xs text-gray-500 dark:text-gray-400">
                                <strong class="font-medium text-gray-700 dark:text-gray-200">{{ $contato->type->label() }}</strong>
                                <span>{{ $contato->happened_at->format('d/m/Y H:i') }}</span>
                                @if ($contato->user)
                                    <span>· {{ $contato->user->name }}</span>
                                @endif
                            </div>

                            <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $contato->summary }}</p>
                        </div>
                    </div>
                @empty
                    <x-admin.empty-state icon="inbox" title="Nenhum contato registrado ainda."
                                         description="Registre a primeira conversa no formulário acima." />
                @endforelse
            </div>
        </x-admin.card>
    </div>

    <div class="space-y-6">
        <x-admin.card title="Etapa">
            <form method="post" action="{{ route('admin.crm.stage', $prospect) }}" class="space-y-3"
                  x-data="{ etapa: '{{ $prospect->stage->value }}' }">
                @csrf
                @method('patch')

                <select name="stage" x-model="etapa" class="{{ $input }}">
                    @foreach ($etapas as $etapa)
                        <option value="{{ $etapa->value }}">{{ $etapa->label() }}</option>
                    @endforeach
                </select>

                <p class="text-xs text-gray-500 dark:text-gray-400" x-show="etapa !== 'perdido'">
                    A mudança fica registrada no histórico, com data e autor.
                </p>

                <div x-show="etapa === 'perdido'" x-cloak>
                    <x-admin.field label="Por que não seguiu" name="lost_reason" required>
                        <input type="text" name="lost_reason" value="{{ old('lost_reason', $prospect->lost_reason) }}"
                               placeholder="Achou caro, sem interesse agora..." class="{{ $input }}">
                    </x-admin.field>
                </div>

                <x-admin.button type="submit" variant="primary" icon="check">Mudar etapa</x-admin.button>
            </form>
        </x-admin.card>

        <x-admin.card title="Próximo passo">
            @if ($prospect->next_action || $prospect->next_action_at)
                <p class="text-sm text-gray-700 dark:text-gray-300">{{ $prospect->next_action ?: '—' }}</p>

                @if ($prospect->next_action_at)
                    <p class="mt-1 text-sm {{ $prospect->atrasado() ? 'font-medium text-error-600 dark:text-error-400' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $prospect->next_action_at->format('d/m/Y') }}
                        {{ $prospect->atrasado() ? '— atrasado' : '' }}
                    </p>
                @endif
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Nenhum combinado. Sem próximo passo, este prospecto some do radar.
                </p>
            @endif
        </x-admin.card>

        <x-admin.card title="Dados">
            <dl class="space-y-3 text-sm">
                @foreach ([
                    'Tipo' => $prospect->kindLabel(),
                    'Contato' => $prospect->contact_name,
                    'E-mail' => $prospect->email,
                    'WhatsApp' => $prospect->whatsapp,
                    'Cidade' => $prospect->city,
                    'Segmento' => $prospect->segment,
                    'Origem' => $prospect->source?->label(),
                    'Responsável' => $prospect->owner?->name,
                    'No funil desde' => $prospect->created_at?->format('d/m/Y'),
                ] as $rotulo => $valor)
                    @if (filled($valor))
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $rotulo }}</dt>
                            <dd class="mt-0.5 text-gray-700 dark:text-gray-300">{{ $valor }}</dd>
                        </div>
                    @endif
                @endforeach

                @if ($prospect->site_url)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Site</dt>
                        <dd class="mt-0.5"><a href="{{ $prospect->site_url }}" target="_blank" rel="noopener" class="text-brand-600 hover:underline dark:text-brand-400">{{ $prospect->site_url }}</a></dd>
                    </div>
                @endif
            </dl>

            @if ($prospect->notes)
                <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Anotações</dt>
                    <p class="mt-1 whitespace-pre-line text-sm text-gray-700 dark:text-gray-300">{{ $prospect->notes }}</p>
                </div>
            @endif

            @if ($cadastro = $prospect->cadastro())
                <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-800">
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Cadastro no portal</dt>
                    <dd class="mt-1">
                        <a href="{{ $prospect->company ? route('admin.companies.show', $cadastro) : route('admin.members.show', $cadastro) }}"
                           class="text-sm text-brand-600 hover:underline dark:text-brand-400">{{ $cadastro->name }}</a>
                    </dd>
                </div>
            @endif
        </x-admin.card>

        <div class="flex flex-wrap gap-2">
            <x-admin.button :href="route('admin.crm.edit', $prospect)" icon="edit">Editar</x-admin.button>
            <x-admin.confirm-form :action="route('admin.crm.destroy', $prospect)"
                                  message="Remover &quot;{{ $prospect->name }}&quot; do funil?">Remover</x-admin.confirm-form>
        </div>
    </div>
</div>
@endsection
