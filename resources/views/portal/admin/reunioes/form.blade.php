@extends('layouts.admin')

@section('title', $reuniao->exists ? 'Editar reunião' : 'Nova reunião')
@section('page_heading', $reuniao->exists ? 'Editar reunião' : 'Nova reunião')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $reuniao->exists ? route('admin.reunioes.update', $reuniao) : route('admin.reunioes.store');

    $pauta = old('agenda', $reuniao->exists
        ? $reuniao->agendaItems->map(fn ($i) => [
            'id' => $i->id, 'title' => $i->title, 'description' => $i->description,
            'presenter' => $i->presenter, 'duration_minutes' => $i->duration_minutes,
        ])->all()
        : []);
@endphp

<form method="post" action="{{ $action }}" class="space-y-6"
      x-data="{ pauta: {{ Js::from($pauta ?: [['id' => null, 'title' => '', 'description' => '', 'presenter' => '', 'duration_minutes' => '']]) }} }">
    @csrf
    @if ($reuniao->exists) @method('put') @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-admin.card title="A reunião">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="Título" name="title" required class="sm:col-span-2">
                        <input type="text" name="title" value="{{ old('title', $reuniao->title) }}" required
                               placeholder="Ex.: Assembleia Geral Ordinária 2026" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Tipo" name="type" required>
                        <select name="type" class="{{ $input }}">
                            @foreach (\App\Enums\MeetingType::options() as $valor => $rotulo)
                                <option value="{{ $valor }}" @selected(old('type', $reuniao->type?->value) === $valor)>{{ $rotulo }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>

                    <x-admin.field label="Situação" name="status" required>
                        <select name="status" class="{{ $input }}">
                            @foreach (\App\Enums\MeetingStatus::options() as $valor => $rotulo)
                                <option value="{{ $valor }}" @selected(old('status', $reuniao->status?->value) === $valor)>{{ $rotulo }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>

                    <x-admin.field label="Início" name="scheduled_at" required>
                        <input type="datetime-local" name="scheduled_at" required
                               value="{{ old('scheduled_at', $reuniao->scheduled_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Término" name="ends_at" hint="Opcional.">
                        <input type="datetime-local" name="ends_at"
                               value="{{ old('ends_at', $reuniao->ends_at?->format('Y-m-d\TH:i')) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Local" name="location" class="sm:col-span-2">
                        <input type="text" name="location" value="{{ old('location', $reuniao->location) }}"
                               placeholder="Ex.: Sede da associação — Rua X, 100" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Link da videochamada" name="online_url" class="sm:col-span-2"
                                   hint="Se for online ou híbrida.">
                        <input type="url" name="online_url" value="{{ old('online_url', $reuniao->online_url) }}"
                               placeholder="https://" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Texto da convocação" name="description" class="sm:col-span-2"
                                   hint="Aparece na página que você envia no WhatsApp.">
                        <textarea name="description" rows="4" class="{{ $input }}">{{ old('description', $reuniao->description) }}</textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="Pauta" subtitle="Arraste a ordem alterando os itens. Linhas sem título são ignoradas.">
                <div class="space-y-3">
                    <template x-for="(item, indice) in pauta" :key="indice">
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <div class="mb-3 flex items-center justify-between">
                                <strong class="text-xs font-semibold uppercase tracking-wide text-gray-400"
                                        x-text="'Item ' + (indice + 1)"></strong>
                                <button type="button" @click="pauta.splice(indice, 1)"
                                        class="text-xs font-medium text-error-600 hover:underline dark:text-error-400">
                                    Remover
                                </button>
                            </div>

                            <input type="hidden" :name="`agenda[${indice}][id]`" :value="item.id ?? ''">

                            <div class="grid gap-3 sm:grid-cols-6">
                                <input type="text" :name="`agenda[${indice}][title]`" x-model="item.title"
                                       placeholder="Título do item" class="{{ $input }} sm:col-span-4">
                                <input type="text" :name="`agenda[${indice}][presenter]`" x-model="item.presenter"
                                       placeholder="Quem apresenta" class="{{ $input }} sm:col-span-2">
                                <textarea :name="`agenda[${indice}][description]`" x-model="item.description" rows="2"
                                          placeholder="Detalhes (opcional)" class="{{ $input }} sm:col-span-5"></textarea>
                                <input type="number" :name="`agenda[${indice}][duration_minutes]`" x-model="item.duration_minutes"
                                       min="1" max="600" placeholder="min" class="{{ $input }}">
                            </div>
                        </div>
                    </template>
                </div>

                <x-admin.button type="button" icon="plus" class="mt-4"
                                @click="pauta.push({ id: null, title: '', description: '', presenter: '', duration_minutes: '' })">
                    Adicionar item
                </x-admin.button>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Confirmações">
                <div class="space-y-4">
                    <x-admin.field label="Prazo para confirmar" name="confirmations_until"
                                   hint="Vazio: aceita até a hora da reunião.">
                        <input type="datetime-local" name="confirmations_until"
                               value="{{ old('confirmations_until', $reuniao->confirmations_until?->format('Y-m-d\TH:i')) }}"
                               class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Quórum mínimo" name="quorum_minimum"
                                   hint="Número de empresas para a assembleia ser válida. Opcional.">
                        <input type="number" name="quorum_minimum" min="1" max="999"
                               value="{{ old('quorum_minimum', $reuniao->quorum_minimum) }}" class="{{ $input }}">
                    </x-admin.field>
                </div>
            </x-admin.card>

            @if ($reuniao->exists)
                <x-admin.card title="Link de convocação">
                    <p class="mb-3 break-all rounded-lg bg-gray-50 p-3 text-xs text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                        {{ $reuniao->publicUrl() }}
                    </p>
                    <x-admin.button :href="route('admin.reunioes.show', $reuniao)" class="w-full">
                        Abrir o painel da reunião
                    </x-admin.button>
                </x-admin.card>
            @endif

            <div class="flex flex-col gap-2">
                <x-admin.button type="submit" variant="primary" icon="check">
                    {{ $reuniao->exists ? 'Salvar alterações' : 'Criar reunião' }}
                </x-admin.button>
                <x-admin.button :href="route('admin.reunioes.index')" variant="ghost">Cancelar</x-admin.button>
            </div>
        </div>
    </div>
</form>
@endsection
