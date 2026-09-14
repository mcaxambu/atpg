@extends('layouts.admin')

@section('title', $member->exists ? 'Editar membro' : 'Novo membro')
@section('page_heading', $member->exists ? 'Editar membro' : 'Novo membro')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $action = $member->exists ? route('admin.members.update', $member) : route('admin.members.store');
    $selectedSpecialties = old('specialties', $member->exists ? $member->specialties->pluck('id')->all() : []);
    $lines = fn ($collection) => $collection->pluck('title')->implode("\n");
@endphp

<form method="post" action="{{ $action }}" enctype="multipart/form-data" class="space-y-6"
      x-data="{ status: '{{ old('status', $member->status?->value ?? 'pending') }}' }">
    @csrf
    @if ($member->exists) @method('put') @endif

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <x-admin.card title="Dados pessoais">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="Nome completo" name="name" required class="sm:col-span-2">
                        <input type="text" name="name" value="{{ old('name', $member->name) }}" required class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Cargo" name="role">
                        <input type="text" name="role" value="{{ old('role', $member->role) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Empresa" name="company_id">
                        <select name="company_id" class="{{ $input }}">
                            <option value="">Profissional independente</option>
                            @foreach ($companies as $company)
                                <option value="{{ $company->id }}" @selected(old('company_id', $member->company_id) == $company->id)>{{ $company->name }}</option>
                            @endforeach
                        </select>
                    </x-admin.field>

                    <x-admin.field label="Cidade" name="city">
                        <input type="text" name="city" value="{{ old('city', $member->city) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Anos de experiência" name="experience_years">
                        <input type="number" name="experience_years" min="0" max="80" value="{{ old('experience_years', $member->experience_years ?? 0) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Resumo profissional" name="summary" class="sm:col-span-2">
                        <textarea name="summary" data-editor rows="5" class="{{ $input }}">{{ old('summary', $member->summary) }}</textarea>
                    </x-admin.field>
                </div>
            </x-admin.card>

            <x-admin.card title="Especialidades">
                @if ($specialties->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Nenhuma especialidade cadastrada.
                        <a href="{{ route('admin.specialties.create') }}" class="text-brand-500 hover:underline">Criar a primeira</a>.
                    </p>
                @else
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($specialties as $specialty)
                            <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-3 py-2.5 text-sm text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                                <input type="checkbox" name="specialties[]" value="{{ $specialty->id }}"
                                       @checked(in_array($specialty->id, $selectedSpecialties))
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

            <x-admin.card title="Contato e redes">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-admin.field label="E-mail" name="email" hint="Recebe os avisos de aprovação e rejeição.">
                        <input type="email" name="email" value="{{ old('email', $member->email) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="WhatsApp" name="whatsapp">
                        <input type="text" name="whatsapp" value="{{ old('whatsapp', $member->whatsapp) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Site" name="site_url">
                        <input type="url" name="site_url" value="{{ old('site_url', $member->site_url) }}" placeholder="https://" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="LinkedIn" name="linkedin_url">
                        <input type="url" name="linkedin_url" value="{{ old('linkedin_url', $member->linkedin_url) }}" placeholder="https://" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Instagram" name="instagram_url" class="sm:col-span-2">
                        <input type="url" name="instagram_url" value="{{ old('instagram_url', $member->instagram_url) }}" placeholder="https://" class="{{ $input }}">
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
                        <x-admin.field label="Motivo da rejeição" name="rejection_reason" hint="Enviado por e-mail ao membro.">
                            <textarea name="rejection_reason" rows="4" class="{{ $input }}">{{ old('rejection_reason', $member->rejection_reason) }}</textarea>
                        </x-admin.field>
                    </div>

                    <label x-show="status === 'approved'" x-cloak class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $member->exists ? $member->is_active : true))
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span>
                            <strong class="block text-sm font-medium text-gray-800 dark:text-gray-200">Publicado no portal</strong>
                            <small class="text-xs text-gray-500 dark:text-gray-400">Desmarque para tirar do ar sem rejeitar.</small>
                        </span>
                    </label>

                    <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                        <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $member->is_featured))
                               class="mt-0.5 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                        <span>
                            <strong class="block text-sm font-medium text-gray-800 dark:text-gray-200">Membro em destaque</strong>
                            <small class="text-xs text-gray-500 dark:text-gray-400">Aparece primeiro na home e no diretório.</small>
                        </span>
                    </label>
                </div>
            </x-admin.card>

            <x-admin.card title="Coluna">
                @include('portal.admin.partials.columnist-fields', [
                    'columnist' => $member->columnist,
                    'tipo' => 'membro',
                ])
            </x-admin.card>

            <x-admin.card title="Foto e avatar">
                @if ($member->photo_path)
                    <div class="mb-4 flex items-center gap-3">
                        <img src="{{ asset('storage/'.$member->photo_path) }}" alt="Foto atual"
                             class="h-20 w-20 rounded-xl object-cover">
                        <label class="flex items-center gap-2 text-sm text-error-600 dark:text-error-400">
                            <input type="checkbox" name="remove_photo" value="1" class="h-4 w-4 rounded border-gray-300 text-error-500">
                            Remover foto
                        </label>
                    </div>
                @endif

                <x-admin.field label="Nova foto" name="photo" hint="JPG, PNG ou WEBP, até 2 MB.">
                    <input type="file" name="photo" accept="image/*"
                           class="block w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/15 dark:file:text-brand-300">
                </x-admin.field>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <x-admin.field label="Iniciais" name="avatar_initials" hint="Usadas sem foto.">
                        <input type="text" name="avatar_initials" maxlength="8" value="{{ old('avatar_initials', $member->avatar_initials) }}" class="{{ $input }}">
                    </x-admin.field>

                    <x-admin.field label="Cor" name="avatar_color">
                        <input type="color" name="avatar_color" value="{{ old('avatar_color', $member->avatar_color ?: '#0f4c81') }}"
                               class="h-11 w-full cursor-pointer rounded-lg border border-gray-200 bg-white p-1 dark:border-gray-700 dark:bg-gray-900">
                    </x-admin.field>
                </div>
            </x-admin.card>

            <div class="flex flex-col gap-2">
                <x-admin.button type="submit" variant="primary" icon="check">
                    {{ $member->exists ? 'Salvar alterações' : 'Cadastrar membro' }}
                </x-admin.button>
                <x-admin.button :href="route('admin.members.index')" variant="ghost">Cancelar</x-admin.button>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
    @vite('resources/js/editor.js')
@endpush
