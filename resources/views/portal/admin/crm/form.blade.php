@extends('layouts.admin')

@section('title', $prospect->exists ? 'Editar prospecto' : 'Novo prospecto')
@section('page_heading', $prospect->exists ? 'Editar prospecto' : 'Novo prospecto')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $prospect->exists ? route('admin.crm.update', $prospect) : route('admin.crm.store');
@endphp

<form method="post" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($prospect->exists) @method('put') @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-admin.card title="Quem é">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="Nome" name="name" required class="sm:col-span-2"
                                   hint="Da empresa, ou da pessoa quando for cadastro individual.">
                        <input type="text" name="name" value="{{ old('name', $prospect->name) }}" required class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Tipo" name="kind" required>
                        <select name="kind" class="{{ $input }}">
                            <option value="{{ \App\Models\Prospect::KIND_COMPANY }}" @selected(old('kind', $prospect->kind) === \App\Models\Prospect::KIND_COMPANY)>Empresa</option>
                            <option value="{{ \App\Models\Prospect::KIND_PERSON }}" @selected(old('kind', $prospect->kind) === \App\Models\Prospect::KIND_PERSON)>Pessoa</option>
                        </select>
                    </x-admin.field>

                    <x-admin.field label="Segmento" name="segment">
                        <input type="text" name="segment" value="{{ old('segment', $prospect->segment) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Pessoa de contato" name="contact_name">
                        <input type="text" name="contact_name" value="{{ old('contact_name', $prospect->contact_name) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Cidade" name="city">
                        <input type="text" name="city" value="{{ old('city', $prospect->city) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="E-mail" name="email">
                        <input type="email" name="email" value="{{ old('email', $prospect->email) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="WhatsApp" name="whatsapp">
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $prospect->whatsapp) }}" placeholder="(42) 99999-9999" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Site" name="site_url" class="sm:col-span-2">
                        <input type="url" name="site_url" value="{{ old('site_url', $prospect->site_url) }}" placeholder="https://" class="{{ $input }}">
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="Anotações"
                          subtitle="O que ajuda a conversa: interesse, quem indicou, o que ficou de pendência.">
                <x-admin.field name="notes">
                    <textarea name="notes" rows="6" class="{{ $input }}">{{ old('notes', $prospect->notes) }}</textarea>
                </x-admin.field>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Acompanhamento">
                <div class="space-y-4">
                    <x-admin.field label="Etapa" name="stage" required>
                        <select name="stage" class="{{ $input }}">
                            @foreach ($etapas as $etapa)
                                <option value="{{ $etapa->value }}" @selected(old('stage', $prospect->stage?->value) === $etapa->value)>{{ $etapa->label() }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>

                    <x-admin.field label="Origem" name="source" required hint="Serve para saber o que traz associado.">
                        <select name="source" class="{{ $input }}">
                            @foreach ($origens as $origem)
                                <option value="{{ $origem->value }}" @selected(old('source', $prospect->source?->value) === $origem->value)>{{ $origem->label() }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>

                    <x-admin.field label="Responsável" name="owner_user_id"
                                   hint="Em branco, fica com você. Prospecto sem dono é prospecto que ninguém cobra.">
                        <select name="owner_user_id" class="{{ $input }}">
                            <option value="">— eu mesmo —</option>
                            @foreach ($responsaveis as $pessoa)
                                <option value="{{ $pessoa->id }}" @selected((int) old('owner_user_id', $prospect->owner_user_id) === $pessoa->id)>{{ $pessoa->name }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="Próximo passo"
                          subtitle="É o que faz o funil andar: sem data, o cartão some do radar.">
                <div class="space-y-4">
                    <x-admin.field label="O que fazer" name="next_action">
                        <input type="text" name="next_action" value="{{ old('next_action', $prospect->next_action) }}"
                               placeholder="Ligar para marcar o café" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Quando" name="next_action_at">
                        <input type="date" name="next_action_at"
                               value="{{ old('next_action_at', $prospect->next_action_at?->format('Y-m-d')) }}" class="{{ $input }}">
                    </x-admin.field>
                </div>
            </x-admin.card>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <x-admin.button type="submit" variant="primary" icon="check">
            {{ $prospect->exists ? 'Salvar' : 'Cadastrar prospecto' }}
        </x-admin.button>

        <x-admin.button :href="$prospect->exists ? route('admin.crm.show', $prospect) : route('admin.crm.index')" variant="ghost">Cancelar</x-admin.button>
    </div>
</form>
@endsection
