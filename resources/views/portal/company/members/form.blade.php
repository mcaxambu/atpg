@extends('layouts.company')

@section('title', $member->exists ? 'Editar colaborador' : 'Novo colaborador')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $member->exists ? route('empresa.membros.update', $member->id) : route('empresa.membros.store');
    $selected = old('specialties', $member->exists ? $member->specialties->pluck('id')->all() : []);
    $lines = fn ($collection) => $collection->pluck('title')->implode("\n");
@endphp

<div>
    <h1 class="text-2xl font-semibold text-gray-800 dark:text-white/90">
        {{ $member->exists ? 'Editar colaborador' : 'Novo colaborador' }}
    </h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
        Depois de salvar, o cadastro vai para análise da associação antes de aparecer no portal.
    </p>
</div>

<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @if ($member->exists) @method('put') @endif

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card title="Dados profissionais">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="Nome completo" name="name" required class="sm:col-span-2">
                        <input type="text" name="name" value="{{ old('name', $member->name) }}" required class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Cargo" name="role">
                        <input type="text" name="role" value="{{ old('role', $member->role) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Cidade" name="city">
                        <input type="text" name="city" value="{{ old('city', $member->city ?: 'Ponta Grossa, PR') }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Anos de experiência" name="experience_years">
                        <input type="number" name="experience_years" min="0" max="80"
                               value="{{ old('experience_years', $member->experience_years ?? 0) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="E-mail" name="email" required hint="Usado para identificar o colaborador.">
                        <input type="email" name="email" value="{{ old('email', $member->email) }}" required class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Resumo profissional" name="summary" class="sm:col-span-2">
                        <textarea name="summary" rows="5" class="{{ $input }}">{{ old('summary', $member->summary) }}</textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="Especialidades" subtitle="Definem em quais filtros do diretório o perfil aparece.">
                @if ($specialties->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhuma especialidade disponível no momento.</p>
                @else
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($specialties as $specialty)
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}"
                                       @checked(in_array($specialty->id, $selected))
                                       class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                                {{ $specialty->name }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </x-admin.card>

            <x-admin.card title="Trajetória" subtitle="Uma entrada por linha.">
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-admin.field label="Experiências" name="experiences">
                        <textarea name="experiences" rows="5" class="{{ $input }}">{{ old('experiences', $member->exists ? $lines($member->experiences) : '') }}</textarea>
                    </x-admin.field>
                    <x-admin.field label="Projetos" name="projects">
                        <textarea name="projects" rows="5" class="{{ $input }}">{{ old('projects', $member->exists ? $lines($member->projects) : '') }}</textarea>
                    </x-admin.field>
                    <x-admin.field label="Certificações" name="certifications">
                        <textarea name="certifications" rows="5" class="{{ $input }}">{{ old('certifications', $member->exists ? $lines($member->certifications) : '') }}</textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>
        </div>

        <div class="space-y-6">
            <x-admin.card title="Foto">
                @if ($member->photo_path)
                    <div class="mb-4 flex items-center gap-3">
                        <img src="{{ asset('storage/'.$member->photo_path) }}" alt="Foto atual" class="h-20 w-20 rounded-xl object-cover">
                        <label class="flex items-center gap-2 text-sm text-error-600 dark:text-error-400">
                            <input type="checkbox" name="remove_photo" value="1" class="h-4 w-4 rounded border-gray-300 text-error-500">
                            Remover
                        </label>
                    </div>
                @endif

                <x-admin.field name="photo" hint="JPG, PNG ou WEBP, até 2 MB.">
                    <input type="file" name="photo" accept="image/*"
                           class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/15 dark:file:text-brand-300">
                </x-admin.field>
            </x-admin.card>

            <x-admin.card title="Redes e links">
                <div class="space-y-4">
                    <x-admin.field label="LinkedIn" name="linkedin_url">
                        <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $member->linkedin_url) }}" placeholder="https://" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="Site" name="site_url">
                        <input type="url" name="site_url" value="{{ old('site_url', $member->site_url) }}" placeholder="https://" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="Instagram" name="instagram_url">
                        <input type="url" name="instagram_url" value="{{ old('instagram_url', $member->instagram_url) }}" placeholder="https://" class="{{ $input }}">
                    </x-admin.field>
                    <x-admin.field label="WhatsApp" name="whatsapp">
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $member->whatsapp) }}" class="{{ $input }}">
                    </x-admin.field>
                </div>
            </x-admin.card>

            <div class="flex flex-col gap-2">
                <x-admin.button type="submit" variant="primary" icon="check">
                    {{ $member->exists ? 'Salvar e enviar para análise' : 'Cadastrar e enviar para análise' }}
                </x-admin.button>
                <x-admin.button :href="route('empresa.membros.index')" variant="ghost">Cancelar</x-admin.button>
            </div>
        </div>
    </div>
</form>
@endsection
