@extends('layouts.admin')

@section('title', $company->exists ? 'Editar empresa' : 'Nova empresa')
@section('page_heading', $company->exists ? 'Editar empresa' : 'Nova empresa')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $company->exists ? route('admin.companies.update', $company) : route('admin.companies.store');
@endphp

<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6"
      x-data="{ status: '{{ old('status', $company->status?->value ?? 'pending') }}' }">
    @csrf
    @if ($company->exists) @method('put') @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-admin.card title="Identificação">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="Nome fantasia" name="name" required class="sm:col-span-2">
                        <input type="text" name="name" value="{{ old('name', $company->name) }}" required class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Razão social" name="legal_name">
                        <input type="text" name="legal_name" value="{{ old('legal_name', $company->legal_name) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="CNPJ" name="cnpj" hint="Com ou sem máscara — validamos os dígitos.">
                        <input type="text" name="cnpj" value="{{ old('cnpj', $company->cnpj) }}" placeholder="00.000.000/0000-00" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Segmento" name="segment">
                        <input type="text" name="segment" value="{{ old('segment', $company->segment) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Iniciais" name="initials" hint="Usadas quando não há logo.">
                        <input type="text" name="initials" maxlength="8" value="{{ old('initials', $company->initials) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Descrição" name="description" class="sm:col-span-2">
                        <textarea name="description" rows="5" class="{{ $input }}">{{ old('description', $company->description) }}</textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="Endereço">
                <div class="grid gap-4 sm:grid-cols-6">
                    <x-admin.field label="CEP" name="zip_code" class="sm:col-span-2">
                        <input type="text" name="zip_code" value="{{ old('zip_code', $company->zip_code) }}" placeholder="00000-000" class="{{ $input }}">
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

                    <x-admin.field label="Cargo do responsável" name="contact_role">
                        <input type="text" name="contact_role" value="{{ old('contact_role', $company->contact_role) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="E-mail" name="email" hint="Recebe os avisos de aprovação e rejeição.">
                        <input type="email" name="email" value="{{ old('email', $company->email) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="WhatsApp" name="whatsapp">
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $company->whatsapp) }}" placeholder="(42) 99999-9999" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Site" name="site_url" class="sm:col-span-2">
                        <input type="url" name="site_url" value="{{ old('site_url', $company->site_url) }}" placeholder="https://" class="{{ $input }}">
                    </x-admin.field>
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Publicação">
                <div class="space-y-4">
                    <x-admin.field label="Situação" name="status" required>
                        <select name="status" x-model="status" class="{{ $input }}">
                            @foreach (\App\Enums\ModerationStatus::options() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>

                    <div x-show="status === 'rejected'" x-cloak>
                        <x-admin.field label="Motivo da rejeição" name="rejection_reason"
                                       hint="Enviado por e-mail ao responsável.">
                            <textarea name="rejection_reason" rows="4" class="{{ $input }}">{{ old('rejection_reason', $company->rejection_reason) }}</textarea>
                        </x-admin.field>
                    </div>

                    <label x-show="status === 'approved'" x-cloak class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $company->exists ? $company->is_active : true))
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span>
                            <strong class="block text-sm font-medium text-gray-800 dark:text-gray-200">Publicado no portal</strong>
                            <small class="text-xs text-gray-500 dark:text-gray-400">Desmarque para tirar do ar sem rejeitar o cadastro.</small>
                        </span>
                    </label>
                </div>
            </x-admin.card>

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

            <div class="flex flex-col gap-2">
                <x-admin.button type="submit" variant="primary" icon="check">
                    {{ $company->exists ? 'Salvar alterações' : 'Cadastrar empresa' }}
                </x-admin.button>
                <x-admin.button :href="route('admin.companies.index')" variant="ghost">Cancelar</x-admin.button>
            </div>
        </div>
    </div>
</form>
@endsection
