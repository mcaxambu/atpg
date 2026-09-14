@extends('layouts.company')

@section('title', $column->title.' | Moderar coluna')

@section('content')
@php
    $input = 'w-full rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-sm text-gray-800 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $colunista = $column->columnist;
@endphp

<div>
    <a href="{{ route('empresa.moderacao.index') }}" class="text-sm text-brand-600 hover:underline dark:text-brand-400">&larr; Voltar para a moderação</a>
</div>

<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-6 lg:col-span-2">
        <x-admin.card>
            <h1 class="text-xl font-semibold text-gray-800 dark:text-white/90">{{ $column->title }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">por {{ $colunista?->byline }}</p>

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
                <form method="post" action="{{ route('empresa.moderacao.aprovar', $column->id) }}">
                    @csrf @method('patch')
                    <x-admin.button type="submit" variant="success" icon="check" class="w-full">Aprovar e publicar</x-admin.button>
                </form>
            @endif

            @if ($column->isApproved() && $column->is_published)
                <form method="post" action="{{ route('empresa.moderacao.despublicar', $column->id) }}">
                    @csrf @method('patch')
                    <x-admin.button type="submit" icon="eye" class="w-full">Tirar do portal</x-admin.button>
                </form>
            @endif

            @unless ($column->isRejected())
                <form method="post" action="{{ route('empresa.moderacao.rejeitar', $column->id) }}" class="mt-4 space-y-3 border-t border-gray-100 pt-4 dark:border-gray-800">
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

        @if ($colunista)
            <x-admin.card title="Quem assina">
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
            </x-admin.card>
        @endif
    </div>
</div>
@endsection
