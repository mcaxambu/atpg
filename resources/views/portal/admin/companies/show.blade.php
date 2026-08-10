@extends('layouts.admin')

@section('title', $company->name)
@section('page_heading', 'Análise de empresa')

@section('content')
@php
    $fields = [
        'Razão social' => $company->legal_name,
        'CNPJ' => $company->cnpj,
        'Segmento' => $company->segment,
        'Cidade' => trim($company->city.($company->state ? '/'.$company->state : ''), '/'),
        'CEP' => $company->zip_code,
        'Endereço' => trim(($company->address ?: '').' '.($company->address_number ?: '')),
        'Bairro' => $company->neighborhood,
        'Responsável' => $company->contact_name,
        'Cargo do responsável' => $company->contact_role,
        'E-mail' => $company->email,
        'WhatsApp' => $company->whatsapp,
        'Site' => $company->site_url,
        'Origem do cadastro' => $company->registration_source === 'public' ? 'Formulário público' : 'Cadastro interno',
        'Membros vinculados' => $company->members_count,
    ];
@endphp

<div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
    <div class="flex gap-4">
        <x-admin.avatar :photo="$company->logo_path" :initials="$company->display_initials" size="h-16 w-16" contain />
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">{{ $company->name }}</h2>
                <x-admin.status-badge :status="$company->status" :published="$company->is_active" />
            </div>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Cadastro recebido em {{ $company->created_at->translatedFormat('d \d\e F \d\e Y, H:i') }}
            </p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <x-admin.button :href="route('admin.companies.index')" variant="ghost">Voltar</x-admin.button>
        @if ($company->isVisible())
            <x-admin.button :href="route('companies.show', $company)" target="_blank" rel="noopener" icon="globe">Ver no portal</x-admin.button>
        @endif
        <x-admin.button :href="route('admin.companies.edit', $company)" icon="edit">Editar</x-admin.button>
        @unless ($company->isRejected())
            <x-admin.reject-dialog :action="route('admin.companies.reject', $company)" :subject="$company->name" />
        @endunless
        @unless ($company->isVisible())
            <x-admin.confirm-form :action="route('admin.companies.approve', $company)" method="patch"
                                  variant="success" icon="check"
                                  message="Aprovar e publicar {{ $company->name }} no portal?">Aprovar e publicar</x-admin.confirm-form>
        @endunless
    </div>
</div>

@if ($company->isRejected() && $company->rejection_reason)
    <div class="rounded-2xl border border-error-200 bg-error-50 p-5 dark:border-error-500/30 dark:bg-error-500/10">
        <strong class="flex items-center gap-2 text-sm font-semibold text-error-800 dark:text-error-200">
            <x-admin.icon name="alert" class="h-4 w-4" />Motivo da rejeição
        </strong>
        <p class="mt-2 text-sm text-error-700 dark:text-error-300">{{ $company->rejection_reason }}</p>
    </div>
@endif

@if ($company->reviewed_at)
    <p class="text-sm text-gray-500 dark:text-gray-400">
        Última decisão em {{ $company->reviewed_at->translatedFormat('d/m/Y \à\s H:i') }}
        @if ($company->reviewer) por <strong class="text-gray-700 dark:text-gray-300">{{ $company->reviewer->name }}</strong>@endif.
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
                        @elseif ($label === 'Site')
                            <a href="{{ $value }}" target="_blank" rel="noopener" class="text-brand-500 hover:underline">{{ $value }}</a>
                        @else
                            {{ $value }}
                        @endif
                    </dd>
                </div>
            @endforeach
        </dl>

        @if ($company->description)
            <div class="mt-6 border-t border-gray-100 pt-5 dark:border-gray-800">
                <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Descrição</dt>
                <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $company->description }}</p>
            </div>
        @endif
    </x-admin.card>

    <div class="space-y-6">
    <x-admin.card title="Acesso ao painel" subtitle="Permite à empresa manter os próprios dados e colaboradores.">
        @php $acesso = $company->accessUser; @endphp

        @if (! $company->isVisible())
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Disponível depois que a empresa estiver aprovada e publicada.
            </p>
        @else
            @if ($acesso)
                <div class="mb-4 space-y-1 text-sm">
                    <strong class="block text-gray-800 dark:text-gray-200">{{ $acesso->name }}</strong>
                    <span class="block break-all text-gray-500 dark:text-gray-400">{{ $acesso->email }}</span>
                    @if ($acesso->hasActivatedAccess())
                        <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-700 dark:bg-success-500/15 dark:text-success-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>Acesso ativo
                        </span>
                    @else
                        <span class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-warning-50 px-2.5 py-1 text-xs font-semibold text-warning-700 dark:bg-warning-500/15 dark:text-warning-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>Convite enviado, senha não definida
                        </span>
                    @endif
                    @if ($acesso->invited_at)
                        <span class="block pt-1 text-xs text-gray-400">
                            Último convite em {{ $acesso->invited_at->translatedFormat('d/m/Y \à\s H:i') }}
                        </span>
                    @endif
                </div>
            @else
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    Nenhum acesso criado. O convite vai para
                    <strong class="break-all text-gray-700 dark:text-gray-300">{{ $company->email ?: 'nenhum e-mail cadastrado' }}</strong>.
                </p>
            @endif

            <form method="post" action="{{ route('admin.companies.invite', $company) }}" class="space-y-2">
                @csrf
                <x-admin.button type="submit" variant="primary" icon="inbox" class="w-full">
                    {{ $acesso?->invited_at ? 'Reenviar convite' : 'Enviar convite' }}
                </x-admin.button>

                @if ($acesso?->hasActivatedAccess())
                    {{-- Reenviar para quem ja usa o painel troca o caminho de acesso; exige gesto explicito. --}}
                    <button type="submit" name="force" value="1"
                            class="w-full rounded-lg px-4 py-2 text-xs font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/[0.04]">
                        Reenviar mesmo assim
                    </button>
                @endif
            </form>
        @endif
    </x-admin.card>

    <x-admin.card title="Checklist de análise">
        <ul class="space-y-3 text-sm">
            @foreach ([
                'Logo enviada' => filled($company->logo_path),
                'CNPJ informado' => filled($company->cnpj),
                'Razão social informada' => filled($company->legal_name),
                'Contato responsável' => filled($company->contact_name),
                'E-mail de contato' => filled($company->email),
                'Endereço completo' => filled($company->address) && filled($company->city),
                'Descrição preenchida' => filled($company->description),
            ] as $label => $done)
                <li class="flex items-center gap-2">
                    <span @class([
                        'grid h-5 w-5 shrink-0 place-items-center rounded-full',
                        'bg-success-100 text-success-700 dark:bg-success-500/20 dark:text-success-300' => $done,
                        'bg-gray-100 text-gray-400 dark:bg-white/[0.06]' => ! $done,
                    ])>
                        <x-admin.icon :name="$done ? 'check' : 'x'" class="h-3 w-3" />
                    </span>
                    <span @class(['text-gray-700 dark:text-gray-300' => $done, 'text-gray-400' => ! $done])>{{ $label }}</span>
                </li>
            @endforeach
        </ul>
    </x-admin.card>
    </div>
</div>
@endsection
