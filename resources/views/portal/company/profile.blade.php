@extends('layouts.company')

@section('title', 'Dados da empresa')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
@endphp

<div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Dados da empresa</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        O que você salvar aqui aparece no portal imediatamente.
    </p>
</div>

<form method="post" action="{{ route('empresa.perfil.update') }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @method('put')

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Apresentação">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="Segmento" name="segment" class="sm:col-span-2">
                        <input type="text" name="segment" value="{{ old('segment', $company->segment) }}"
                               placeholder="Ex.: Desenvolvimento de software" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Descrição" name="description" class="sm:col-span-2"
                                   hint="Conte o que a empresa faz, para quem e o que a diferencia.">
                        <textarea name="description" data-editor rows="6" class="{{ $input }}">{{ old('description', $company->description) }}</textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="Endereço">
                <div class="grid gap-4 sm:grid-cols-6">
                    <x-admin.field label="CEP" name="zip_code" class="sm:col-span-2">
                        <input type="text" name="zip_code" value="{{ old('zip_code', $company->zip_code) }}" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="Endereço" name="address" class="sm:col-span-3">
                        <input type="text" name="address" value="{{ old('address', $company->address) }}" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="Número" name="address_number">
                        <input type="text" name="address_number" value="{{ old('address_number', $company->address_number) }}" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="Bairro" name="neighborhood" class="sm:col-span-2">
                        <input type="text" name="neighborhood" value="{{ old('neighborhood', $company->neighborhood) }}" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="Cidade" name="city" class="sm:col-span-3">
                        <input type="text" name="city" value="{{ old('city', $company->city) }}" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="UF" name="state">
                        <input type="text" name="state" maxlength="2" value="{{ old('state', $company->state) }}" class="{{ $input }} uppercase">
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="Contato">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="Nome do responsável" name="contact_name">
                        <input type="text" name="contact_name" value="{{ old('contact_name', $company->contact_name) }}" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="Cargo" name="contact_role">
                        <input type="text" name="contact_role" value="{{ old('contact_role', $company->contact_role) }}" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="WhatsApp" name="whatsapp">
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $company->whatsapp) }}" placeholder="(42) 99999-9999" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="Site" name="site_url">
                        <input type="url" name="site_url" value="{{ old('site_url', $company->site_url) }}" placeholder="https://" class="{{ $input }}">
                    </x-admin.field>
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Logo">
                @if ($company->logo_path)
                    <img src="{{ asset('storage/'.$company->logo_path) }}" alt="Logo atual"
                         class="mb-4 h-24 w-full rounded-xl border border-gray-200 bg-white object-contain p-2 dark:border-gray-700">
                @endif

                <x-admin.field name="logo" hint="JPG, PNG, WEBP ou SVG, até 2 MB.">
                    <input type="file" name="logo" accept="image/*"
                           class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/15 dark:file:text-brand-300">
                </x-admin.field>
            </x-admin.card>

            {{-- Identificacao juridica e o que a associacao aprovou: so ela altera. --}}
            <x-admin.card title="Identificação">
                <dl class="space-y-3 text-sm">
                    @foreach ([
                        'Nome fantasia' => $company->name,
                        'Razão social' => $company->legal_name,
                        'CNPJ' => $company->cnpj,
                    ] as $rotulo => $valor)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $rotulo }}</dt>
                            <dd class="text-gray-800 dark:text-gray-200">{{ $valor ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-4 rounded-lg bg-gray-50 p-3 text-xs leading-relaxed text-gray-500 dark:bg-white/[0.03] dark:text-gray-400">
                    Esses dados foram validados na aprovação. Para alterá-los, fale com a associação.
                </p>
            </x-admin.card>

            <x-admin.button type="submit" variant="primary" icon="check" class="w-full">Salvar alterações</x-admin.button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    @vite('resources/js/editor.js')
@endpush
