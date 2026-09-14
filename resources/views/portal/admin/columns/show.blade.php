@extends('layouts.admin')

@section('title', $column->title.' | Coluna')
@section('page_heading', 'Analisar coluna')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $colunista = $column->columnist;
@endphp

<div>
    <a href="{{ route('admin.columns.index') }}" class="text-sm text-brand-600 hover:underline dark:text-brand-400">&larr; Voltar para as colunas</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-admin.card>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="min-w-0">
                    <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $column->title }}</h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Por {{ $colunista?->byline ?? 'sem colunista' }}
                    </p>
                </div>
                <x-admin.status-badge :status="$column->status" :published="$column->is_published" />
            </div>

            @if ($column->cover_image)
                <img src="{{ asset('storage/'.$column->cover_image) }}" alt="Capa"
                     class="mt-4 h-56 w-full rounded-xl border border-gray-200 object-cover dark:border-gray-700">
            @endif

            @if ($column->excerpt)
                <p class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:bg-white/[0.03] dark:text-gray-400">
                    {{ $column->excerpt }}
                </p>
            @endif
        </x-admin.card>

        <x-admin.card title="Texto">
            <div class="prose-admin text-sm leading-relaxed text-gray-700 dark:text-gray-300">
                {!! $column->rendered_body !!}
            </div>
        </x-admin.card>
    </div>

    <div class="space-y-6">
        <x-admin.card title="Decisão">
            @if ($column->isPending() || $column->isRejected())
                <form method="post" action="{{ route('admin.columns.approve', $column) }}">
                    @csrf @method('patch')
                    <x-admin.button type="submit" variant="success" icon="check" class="w-full">Aprovar e publicar</x-admin.button>
                </form>
            @endif

            @if ($column->isApproved() && $column->is_published)
                <form method="post" action="{{ route('admin.columns.unpublish', $column) }}">
                    @csrf @method('patch')
                    <x-admin.button type="submit" icon="eye" class="w-full">Tirar do portal</x-admin.button>
                </form>
            @endif

            @if ($column->isApproved() && ! $column->is_published)
                <form method="post" action="{{ route('admin.columns.publish', $column) }}">
                    @csrf @method('patch')
                    <x-admin.button type="submit" variant="success" icon="check" class="w-full">Devolver ao portal</x-admin.button>
                </form>
            @endif

            @unless ($column->isRejected())
                <form method="post" action="{{ route('admin.columns.reject', $column) }}" class="mt-4 space-y-3 border-t border-gray-100 pt-4 dark:border-gray-800">
                    @csrf @method('patch')
                    <x-admin.field label="Motivo da devolução" name="rejection_reason"
                                   hint="O colunista vê este texto no painel dele e pode ajustar.">
                        <textarea name="rejection_reason" rows="3" class="{{ $input }}"></textarea>
                    </x-admin.field>
                    <x-admin.button type="submit" variant="danger" icon="close" class="w-full">Devolver ao colunista</x-admin.button>
                </form>
            @else
                <p class="mt-4 rounded-lg bg-error-50 px-3 py-2 text-xs text-error-700 dark:bg-error-500/10 dark:text-error-300">
                    <strong>Devolvida:</strong> {{ $column->rejection_reason ?: 'sem motivo registrado' }}
                </p>
            @endunless
        </x-admin.card>

        <x-admin.card title="Colunista">
            @if ($colunista)
                <div class="flex items-center gap-3">
                    <x-admin.avatar :photo="$colunista->photo_path" :initials="$colunista->initials"
                                    :contain="$colunista->isCompany()" />
                    <div class="min-w-0">
                        <strong class="block truncate text-sm text-gray-800 dark:text-white/90">{{ $colunista->display_name }}</strong>
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $colunista->isCompany() ? 'Empresa associada' : 'Membro' }}
                        </span>
                    </div>
                </div>

                @if ($colunista->isPubliclyVisible())
                    <x-admin.button :href="route('colunas.show', $colunista->slug)" target="_blank"
                                    icon="globe" class="mt-4 w-full">Ver página do colunista</x-admin.button>
                @else
                    <p class="mt-3 rounded-lg bg-warning-50 px-3 py-2 text-xs text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">
                        O cadastro de origem está fora do ar, então a página do colunista não aparece no portal.
                    </p>
                @endif
            @else
                <p class="text-sm text-gray-500 dark:text-gray-400">Coluna sem colunista vinculado.</p>
            @endif
        </x-admin.card>

        @if ($column->reviewed_at)
            <x-admin.card title="Última análise">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $column->reviewed_at->format('d/m/Y \à\s H:i') }}
                    @if ($column->reviewer) por {{ $column->reviewer->name }} @endif
                </p>
            </x-admin.card>
        @endif
    </div>
</div>
@endsection
