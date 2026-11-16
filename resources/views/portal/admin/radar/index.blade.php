@extends('layouts.admin')

@section('title', 'Radar')
@section('page_heading', 'Radar')

@section('content')
{{--
    Fila de SUGESTÃO, não de publicação.

    Cada cartão mostra, lado a lado, o que a leitura automática entendeu e o
    link do original — porque conferir a data contra a fonte é exatamente o
    trabalho que a tela existe para apoiar.
--}}
<x-admin.card class="mb-6" title="O que o radar encontrou"
              subtitle="Varredura diária de eventos, notícias e editais de tecnologia dos Campos Gerais. Nada vai ao portal sem você clicar.">
    <x-slot:actions>
        @if ($ultimaVarredura)
            <span class="text-xs text-gray-500 dark:text-gray-400">
                última coleta {{ \Illuminate\Support\Carbon::parse($ultimaVarredura)->diffForHumans() }}
            </span>
        @endif
    </x-slot:actions>

    <div class="flex flex-wrap gap-2">
        @foreach (\App\Enums\RadarStatus::cases() as $caso)
            <a href="{{ route('admin.radar.index', ['status' => $caso->value]) }}"
               @class([
                   'rounded-lg border px-3 py-2 text-sm font-medium transition',
                   'border-brand-300 bg-brand-50 text-brand-700 dark:border-brand-500/40 dark:bg-brand-500/10 dark:text-brand-300' => $status === $caso,
                   'border-gray-200 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.04]' => $status !== $caso,
               ])>
                {{ $caso->label() }}
                <span class="ml-1 text-xs text-gray-400">{{ $contagens[$caso->value] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
        <a href="{{ route('admin.radar.index', ['status' => $status->value]) }}"
           @class(['text-xs font-medium', 'text-brand-600 dark:text-brand-400' => ! $tipo, 'text-gray-500' => $tipo])>Todos os tipos</a>
        @foreach (\App\Enums\RadarTipo::options() as $valor => $rotulo)
            @continue($valor === 'irrelevante')
            <a href="{{ route('admin.radar.index', ['status' => $status->value, 'tipo' => $valor]) }}"
               @class(['text-xs font-medium', 'text-brand-600 dark:text-brand-400' => $tipo === $valor, 'text-gray-500' => $tipo !== $valor])>{{ $rotulo }}</a>
        @endforeach
    </div>
</x-admin.card>

<div class="space-y-4">
    @forelse ($itens as $item)
        <x-admin.card>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 flex-1">
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        @if ($item->tipo)
                            <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $item->tipo->badgeClasses() }}">
                                {{ $item->tipo->label() }}
                            </span>
                        @endif
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $item->nomeDaFonte() }}</span>

                        {{-- Confiança baixa não esconde o item: avisa que a
                             conferência importa mais neste aqui. --}}
                        @if ($item->confianca !== null && $item->confianca < 60)
                            <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2.5 py-1 text-xs font-semibold text-warning-700 dark:bg-warning-500/15 dark:text-warning-300">
                                <x-admin.icon name="alert" class="h-3.5 w-3.5" /> confira com atenção
                            </span>
                        @endif

                        @if ($item->jaPassou())
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-500 dark:bg-white/[0.06] dark:text-gray-400">
                                data já passou
                            </span>
                        @endif
                    </div>

                    <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">
                        {{ $item->titulo ?: $item->titulo_original }}
                    </h3>

                    @if ($item->resumo)
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $item->resumo }}</p>
                    @endif

                    <dl class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
                        @if ($item->data_inicio)
                            <div>
                                <dt class="inline font-medium">Quando:</dt>
                                <dd class="inline">
                                    {{ $item->data_inicio->translatedFormat('d/m/Y') }}@if ($item->data_fim && ! $item->data_fim->isSameDay($item->data_inicio)) a {{ $item->data_fim->translatedFormat('d/m/Y') }}@endif
                                    @if ($item->hora_inicio) às {{ $item->hora_inicio }} @endif
                                </dd>
                            </div>
                        @endif
                        @if ($item->local)
                            <div><dt class="inline font-medium">Onde:</dt> <dd class="inline">{{ $item->local }}</dd></div>
                        @endif
                        @if ($item->organizador)
                            <div><dt class="inline font-medium">Quem faz:</dt> <dd class="inline">{{ $item->organizador }}</dd></div>
                        @endif
                    </dl>

                    @if ($item->motivo)
                        <p class="mt-2 text-xs italic text-gray-400">{{ $item->motivo }}</p>
                    @endif

                    @if ($item->erro)
                        <p class="mt-2 text-xs text-error-600 dark:text-error-400">{{ $item->erro }}</p>
                    @endif

                    <a href="{{ $item->url }}" target="_blank" rel="noopener noreferrer"
                       class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                        <x-admin.icon name="eye" class="h-3.5 w-3.5" /> abrir a fonte
                    </a>
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    @if ($item->status === \App\Enums\RadarStatus::Novo)
                        <x-admin.confirm-form :action="route('admin.radar.publicar', $item)" method="post"
                                              variant="primary" icon="plus"
                                              message="Criar rascunho a partir deste item? Ele nasce despublicado para você conferir a data.">
                            Criar rascunho
                        </x-admin.confirm-form>

                        <x-admin.confirm-form :action="route('admin.radar.descartar', $item)" method="post"
                                              icon="trash" message="Descartar este item?">
                            Descartar
                        </x-admin.confirm-form>
                    @elseif ($item->status === \App\Enums\RadarStatus::Descartado)
                        <x-admin.confirm-form :action="route('admin.radar.restaurar', $item)" method="post"
                                              icon="restore" message="Devolver este item para a fila?">
                            Devolver à fila
                        </x-admin.confirm-form>
                    @elseif ($item->publicado_id)
                        <x-admin.button
                            :href="$item->publicado_type === \App\Models\Event::class
                                ? route('admin.cms.events.edit', $item->publicado_id)
                                : route('admin.cms.posts.edit', $item->publicado_id)"
                            icon="edit">Abrir o rascunho</x-admin.button>
                    @endif
                </div>
            </div>
        </x-admin.card>
    @empty
        <x-admin.card>
            <x-admin.empty-state icon="search" title="Nada nesta lista."
                                 description="A varredura roda toda madrugada. Se a fila está vazia, é porque as fontes não publicaram nada de tecnologia da região." />
        </x-admin.card>
    @endforelse
</div>

@if ($itens->hasPages())
    <div class="mt-6">{{ $itens->links() }}</div>
@endif
@endsection
