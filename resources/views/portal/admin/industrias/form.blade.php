@extends('layouts.admin')

@section('title', $industria->exists ? $industria->name : 'Nova indústria')
@section('page_heading', $industria->exists ? 'Editar indústria' : 'Nova indústria')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $marcadas = old('especialidades', $industria->exists ? $industria->specialties->pluck('id')->all() : []);
@endphp

<form method="post" action="{{ $industria->exists ? route('admin.industrias.update', $industria) : route('admin.industrias.store') }}" class="space-y-6">
    @csrf
    @if ($industria->exists) @method('put') @endif

    <x-admin.card title="A indústria">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.field label="Nome" name="name" required>
                <input name="name" value="{{ old('name', $industria->name) }}" required class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Razão social" name="legal_name">
                <input name="legal_name" value="{{ old('legal_name', $industria->legal_name) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Setor" name="sector" hint="Ex.: alimentos, metalurgia, papel e celulose.">
                <input name="sector" value="{{ old('sector', $industria->sector) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Porte" name="size">
                <select name="size" class="{{ $input }}">
                    <option value="">Não informado</option>
                    @foreach (\App\Models\Industry::PORTES as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected(old('size', $industria->size) === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </x-admin.field>

            <x-admin.field label="Funcionários" name="employees">
                <input type="number" name="employees" min="0" value="{{ old('employees', $industria->employees) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="CNPJ" name="cnpj">
                <input name="cnpj" value="{{ old('cnpj', $industria->cnpj) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Bairro" name="neighborhood">
                <input name="neighborhood" value="{{ old('neighborhood', $industria->neighborhood) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Cidade" name="city">
                <input name="city" value="{{ old('city', $industria->city ?: 'Ponta Grossa') }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Endereço" name="address" class="sm:col-span-2">
                <input name="address" value="{{ old('address', $industria->address) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Site" name="site_url" class="sm:col-span-2">
                <input name="site_url" type="url" placeholder="https://" value="{{ old('site_url', $industria->site_url) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="O que ela faz" name="description" class="sm:col-span-2"
                           hint="Visível para os associados.">
                <textarea name="description" rows="4" class="{{ $input }}">{{ old('description', $industria->description) }}</textarea>
            </x-admin.field>
        </div>

        <label class="mt-4 flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $industria->exists ? $industria->is_active : true))
                   class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
            Ativa (aparece para os associados)
        </label>
    </x-admin.card>

    {{--
        O coração do recurso.

        Sem necessidade marcada aqui, a indústria entra no cadastro mas fica
        fora de todo cruzamento — e o cruzamento é o motivo desta tela existir.
    --}}
    <x-admin.card title="Do que ela precisa"
                  subtitle="É isto que cruza com o que as associadas sabem fazer. Indústria sem nada marcado não aparece em nenhuma busca por especialidade.">
        @if ($especialidades->isEmpty())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Nenhuma especialidade cadastrada.
                <a href="{{ route('admin.specialties.create') }}" class="text-brand-500 hover:underline">Criar a primeira</a>.
            </p>
        @else
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($especialidades as $especialidade)
                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                        <input type="checkbox" name="especialidades[]" value="{{ $especialidade->id }}"
                               @checked(in_array($especialidade->id, $marcadas))
                               class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        {{ $especialidade->name }}
                    </label>
                @endforeach
            </div>
        @endif
    </x-admin.card>

    {{-- Daqui para baixo, só a diretoria vê. --}}
    <x-admin.card title="Contato e histórico"
                  subtitle="Só a diretoria enxerga este bloco. Os associados veem a indústria e a necessidade, não o telefone de quem decide.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-admin.field label="Quem é o contato" name="contact_name">
                <input name="contact_name" value="{{ old('contact_name', $industria->contact_name) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Cargo" name="contact_role">
                <input name="contact_role" value="{{ old('contact_role', $industria->contact_role) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="E-mail" name="contact_email">
                <input type="email" name="contact_email" value="{{ old('contact_email', $industria->contact_email) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Telefone" name="contact_phone">
                <input name="contact_phone" value="{{ old('contact_phone', $industria->contact_phone) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Último contato" name="last_contact_at">
                <input type="date" name="last_contact_at" value="{{ old('last_contact_at', $industria->last_contact_at?->format('Y-m-d')) }}" class="{{ $input }}">
            </x-admin.field>

            <x-admin.field label="Anotações" name="notes" class="sm:col-span-2"
                           hint="Quem já conversou, o que foi dito, o que ficou de fazer.">
                <textarea name="notes" rows="4" class="{{ $input }}">{{ old('notes', $industria->notes) }}</textarea>
            </x-admin.field>
        </div>
    </x-admin.card>

    <div class="flex flex-wrap gap-2">
        <x-admin.button type="submit" variant="primary" icon="check">
            {{ $industria->exists ? 'Salvar' : 'Cadastrar' }}
        </x-admin.button>
        <x-admin.button :href="route('admin.industrias.index')" variant="ghost">Cancelar</x-admin.button>
    </div>
</form>
@endsection
