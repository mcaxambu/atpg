@extends('layouts.admin')

@section('title', $job->title.' | Vaga')
@section('page_heading', 'Analisar vaga')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $linhas = fn (?string $texto) => collect(preg_split('/\r\n|\r|\n/', (string) $texto))->map(fn ($l) => trim($l))->filter();
@endphp

<div>
    <a href="{{ route('admin.jobs.index') }}" class="text-sm text-brand-600 hover:underline dark:text-brand-400">&larr; Voltar para as vagas</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-admin.card>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $job->title }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $job->company?->name }}</p>
                </div>
                <x-admin.status-badge :status="$job->status" :published="$job->is_active" />
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ([$job->type->label(), $job->workplace->label(), $job->locationLabel(), $job->seniority, $job->salaryLabel()] as $etiqueta)
                    @if ($etiqueta)
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-white/[0.06] dark:text-gray-300">{{ $etiqueta }}</span>
                    @endif
                @endforeach
            </div>
        </x-admin.card>

        <x-admin.card title="Descrição">
            <div class="prose-admin text-sm leading-relaxed text-gray-700 dark:text-gray-300">{!! $job->rendered_description !!}</div>
        </x-admin.card>

        @if (filled($job->requirements))
            <x-admin.card title="Requisitos">
                <ul class="list-inside list-disc space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($linhas($job->requirements) as $linha)
                        <li>{{ $linha }}</li>
                    @endforeach
                </ul>
            </x-admin.card>
        @endif

        @if (filled($job->benefits))
            <x-admin.card title="Benefícios">
                <ul class="list-inside list-disc space-y-1 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($linhas($job->benefits) as $linha)
                        <li>{{ $linha }}</li>
                    @endforeach
                </ul>
            </x-admin.card>
        @endif
    </div>

    <div class="space-y-6">
        <x-admin.card title="Decisão">
            @if ($job->isPending() || $job->isRejected())
                <form method="post" action="{{ route('admin.jobs.approve', $job) }}">
                    @csrf @method('patch')
                    <x-admin.button type="submit" variant="success" icon="check" class="w-full">Aprovar e publicar</x-admin.button>
                </form>
            @endif

            @if ($job->isApproved() && $job->is_active)
                <form method="post" action="{{ route('admin.jobs.unpublish', $job) }}">
                    @csrf @method('patch')
                    <x-admin.button type="submit" icon="eye" class="w-full">Tirar do portal</x-admin.button>
                </form>
            @endif

            @if ($job->isApproved() && ! $job->is_active)
                <form method="post" action="{{ route('admin.jobs.publish', $job) }}">
                    @csrf @method('patch')
                    <x-admin.button type="submit" variant="success" icon="check" class="w-full">Devolver ao portal</x-admin.button>
                </form>
            @endif

            @unless ($job->isRejected())
                <form method="post" action="{{ route('admin.jobs.reject', $job) }}" class="mt-4 space-y-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                    @csrf @method('patch')
                    <x-admin.field label="Motivo da rejeição" name="rejection_reason"
                                   hint="A empresa vê este texto no painel dela.">
                        <textarea name="rejection_reason" rows="3" class="{{ $input }}"></textarea>
                    </x-admin.field>
                    <x-admin.button type="submit" variant="danger" icon="close" class="w-full">Rejeitar vaga</x-admin.button>
                </form>
            @else
                <p class="mt-4 rounded-lg bg-error-50 px-3 py-2 text-xs text-error-700 dark:bg-error-500/10 dark:text-error-300">
                    <strong>Rejeitada:</strong> {{ $job->rejection_reason ?: 'sem motivo registrado' }}
                </p>
            @endunless
        </x-admin.card>

        <x-admin.card title="Situação">
            <dl class="space-y-3 text-sm">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Prazo</dt>
                    <dd class="mt-0.5 text-gray-800 dark:text-white/90">
                        @if ($job->closes_at)
                            {{ $job->closes_at->format('d/m/Y') }}
                            @if ($job->isExpired()) <span class="text-error-600">(vencido)</span> @endif
                        @else
                            Sem data de encerramento
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Candidaturas</dt>
                    <dd class="mt-0.5 text-gray-800 dark:text-white/90">{{ $job->applications_count }}</dd>
                    <dd class="mt-1 text-xs text-gray-400">
                        Os dados dos candidatos são da empresa; a associação vê apenas a contagem.
                    </dd>
                </div>
                @if ($job->reviewed_at)
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-gray-500">Última análise</dt>
                        <dd class="mt-0.5 text-gray-800 dark:text-white/90">{{ $job->reviewed_at->format('d/m/Y H:i') }}</dd>
                    </div>
                @endif
            </dl>
        </x-admin.card>

        @if ($job->isPubliclyVisible())
            <x-admin.button :href="route('vagas.show', $job)" target="_blank" icon="globe" class="w-full">Ver no portal</x-admin.button>
        @endif

        <x-admin.confirm-form :action="route('admin.jobs.destroy', $job)"
                              message="Remover &quot;{{ $job->title }}&quot;?">Remover vaga</x-admin.confirm-form>
    </div>
</div>
@endsection
