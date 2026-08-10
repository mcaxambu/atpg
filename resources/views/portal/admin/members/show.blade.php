@extends('layouts.admin')

@section('title', $member->name)
@section('page_heading', 'Análise de membro')

@section('content')
@php
    $fields = [
        'Cargo' => $member->role,
        'Empresa' => $member->company?->name ?? 'Profissional independente',
        'Cidade' => $member->city,
        'Experiência' => $member->experience_years.' anos',
        'E-mail' => $member->email,
        'WhatsApp' => $member->whatsapp,
        'Site' => $member->site_url,
        'LinkedIn' => $member->linkedin_url,
        'Instagram' => $member->instagram_url,
        'Origem do cadastro' => $member->registration_source === 'public' ? 'Formulário público' : 'Cadastro interno',
    ];
@endphp

<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="flex gap-4">
        <x-admin.avatar :photo="$member->photo_path" :initials="$member->avatar_initials" :color="$member->avatar_color" size="h-16 w-16" />
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $member->name }}</h2>
                <x-admin.status-badge :status="$member->status" :published="$member->is_active" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Cadastro recebido em {{ $member->created_at->translatedFormat('d \d\e F \d\e Y, H:i') }}
            </p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-admin.button :href="route('admin.members.index')" variant="ghost">Voltar</x-admin.button>
        @if ($member->isPubliclyVisible())
            <x-admin.button :href="route('members.show', $member)" target="_blank" rel="noopener" icon="globe">Ver no portal</x-admin.button>
        @endif
        <x-admin.button :href="route('admin.members.edit', $member)" icon="edit">Editar</x-admin.button>
        @unless ($member->isRejected())
            <x-admin.reject-dialog :action="route('admin.members.reject', $member)" :subject="$member->name" />
        @endunless
        @unless ($member->isVisible())
            <x-admin.confirm-form :action="route('admin.members.approve', $member)" method="patch"
                                  variant="success" icon="check"
                                  message="Aprovar e publicar {{ $member->name }} no portal?">Aprovar e publicar</x-admin.confirm-form>
        @endunless
    </div>
</div>

@if ($member->isRejected() && $member->rejection_reason)
    <div class="rounded-2xl border border-error-200 bg-error-50 p-5 dark:border-error-500/30 dark:bg-error-500/10">
        <strong class="flex items-center gap-2 text-sm font-semibold text-error-800 dark:text-error-200">
            <x-admin.icon name="alert" class="h-4 w-4" />Motivo da rejeição
        </strong>
        <p class="mt-2 text-sm text-error-700 dark:text-error-300">{{ $member->rejection_reason }}</p>
    </div>
@endif

@if ($member->isVisible() && $member->company && ! $member->company->isVisible())
    <div class="flex items-start gap-3 rounded-2xl border border-warning-200 bg-warning-50 p-5 dark:border-warning-500/30 dark:bg-warning-500/10">
        <x-admin.icon name="alert" class="mt-0.5 h-5 w-5 shrink-0 text-warning-600 dark:text-warning-400" />
        <p class="text-sm text-warning-800 dark:text-warning-200">
            Este membro está aprovado, mas <strong>não aparece no portal</strong> porque a empresa
            <a href="{{ route('admin.companies.show', $member->company) }}" class="underline">{{ $member->company->name }}</a>
            ainda não está publicada.
        </p>
    </div>
@endif

@if ($member->reviewed_at)
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Última decisão em {{ $member->reviewed_at->translatedFormat('d/m/Y \à\s H:i') }}
        @if ($member->reviewer) por <strong class="text-gray-700 dark:text-gray-300">{{ $member->reviewer->name }}</strong>@endif.
    </p>
@endif

<div class="grid gap-6 xl:grid-cols-3">
    <x-admin.card class="xl:col-span-2" title="Dados do cadastro">
        <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">
            @foreach ($fields as $label => $value)
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ $label }}</dt>
                    <dd class="mt-0.5 break-words text-sm text-gray-800 dark:text-gray-200">
                        @if (blank($value))
                            <span class="text-gray-400">Não informado</span>
                        @elseif (in_array($label, ['Site', 'LinkedIn', 'Instagram'], true))
                            <a href="{{ $value }}" target="_blank" rel="noopener" class="text-brand-500 hover:underline">{{ $value }}</a>
                        @else
                            {{ $value }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>

        @if ($member->summary)
            <div class="mt-6 border-t border-gray-100 pt-5 dark:border-gray-800">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Resumo profissional</dt>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $member->summary }}</p>
            </div>
        @endif
    </x-admin.card>

    <x-admin.card title="Trajetória">
        @foreach ([
            ['Especialidades', $member->specialties->pluck('name')],
            ['Experiências', $member->experiences->pluck('title')],
            ['Projetos', $member->projects->pluck('title')],
            ['Certificações', $member->certifications->pluck('title')],
        ] as [$label, $items])
            <div class="mb-5 last:mb-0">
                <dt class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">{{ $label }}</dt>
                @if ($items->isEmpty())
                    <p class="text-sm text-gray-400">Nada informado</p>
                @else
                    <ul class="space-y-1.5">
                        @foreach ($items as $item)
                            <li class="flex gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400"></span>{{ $item }}
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        @endforeach
    </x-admin.card>
</div>
@endsection
