{{--
    Recepção da reunião — para abrir num tablet na porta.

    Tela própria, fora do layout do painel: aqui quem opera está em pé, com uma
    mão só, e precisa achar o nome rápido. Nomes grandes, alvo de toque grande,
    sem menu lateral e sem nada que leve para outro lugar por engano.
--}}
<!doctype html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>Recepção | {{ $reuniao->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/qrcode.js'])
</head>
<body class="min-h-full bg-gray-50"
      x-data="{
          busca: '',
          /*
              Recarrega sozinho para mostrar quem fez check-in pelo celular.
              Só quando a busca está vazia: recarregar no meio de uma digitação
              apagaria o que a pessoa está escrevendo na porta.
          */
          init() {
              setInterval(() => { if (this.busca === '') window.location.reload(); }, 20000);
          }
      }">

    <header class="sticky top-0 z-10 border-b border-gray-200 bg-white/95 px-5 py-4 backdrop-blur">
        <div class="mx-auto flex max-w-5xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <h1 class="truncate text-xl font-bold text-gray-900">{{ $reuniao->title }}</h1>
                <p class="text-sm text-gray-500">
                    {{ $reuniao->scheduled_at?->translatedFormat('l, d/m') }} · {{ $reuniao->location ?: 'sem local informado' }}
                </p>
            </div>

            <div class="flex items-center gap-6">
                @if ($reuniao->checkinAberto())
                    <div class="text-right">
                        <span class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Código da sala</span>
                        <strong class="block font-mono text-3xl font-bold tracking-widest text-brand-600">{{ $reuniao->codigoDeCheckin() }}</strong>
                    </div>
                @endif

                <div class="text-right">
                    <span class="text-[11px] font-medium uppercase tracking-wide text-gray-400">Chegaram</span>
                    <strong class="block text-3xl font-bold text-gray-900">
                        {{ $reuniao->checkedInCount() }}<span class="text-lg font-normal text-gray-400">/{{ $presencas->count() }}</span>
                    </strong>
                </div>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-5 py-6">
        @unless ($reuniao->checkinAberto())
            <div class="mb-5 rounded-xl border border-warning-200 bg-warning-50 px-5 py-4 text-sm text-warning-800">
                O check-in desta reunião está fechado — ele abre uma hora antes e fecha três horas depois do fim.
                Dá para marcar a chegada por aqui mesmo assim? Não: a marcação segue a mesma janela.
            </div>
        @endunless

        @if (session('status'))
            <div class="mb-5 rounded-xl border border-success-200 bg-success-50 px-5 py-3 text-sm font-medium text-success-800">
                {{ session('status') }}
            </div>
        @endif

        {{--
            Este QR é diferente do cartaz plastificado: ele JÁ CARREGA o código
            desta reunião, então escanear basta — não há nada para digitar.
            Pode ficar nesta tela, virada para quem chega, ou no telão.
        --}}
        @if ($reuniao->checkinAberto())
            <details class="mb-5 rounded-xl border border-gray-200 bg-white p-5">
                <summary class="cursor-pointer text-base font-semibold text-gray-900">
                    Mostrar QR code desta reunião
                    <span class="ml-1 text-sm font-normal text-gray-500">— escaneou, presença registrada</span>
                </summary>

                <div class="mt-5 text-center">
                    <canvas class="mx-auto" data-qr="{{ route('empresa.checkin', ['c' => $reuniao->codigoDeCheckin()]) }}" data-qr-size="300"></canvas>
                    <p class="mt-3 text-sm text-gray-500">
                        Vire a tela para quem chega. Quem preferir digitar usa o código
                        <strong class="font-mono text-gray-900">{{ $reuniao->codigoDeCheckin() }}</strong>.
                    </p>
                </div>
            </details>
        @endif

        <input type="search" x-model="busca" placeholder="Buscar empresa…"
               class="mb-5 w-full rounded-xl border border-gray-300 bg-white px-5 py-4 text-lg text-gray-900 placeholder:text-gray-400 focus:border-brand-400 focus:outline-none focus:ring-4 focus:ring-brand-500/15">

        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($presencas as $presenca)
                <form method="post" action="{{ route('admin.reunioes.checkin', $reuniao) }}"
                      x-show="busca === '' || '{{ Str::lower($presenca->company?->name) }}'.includes(busca.toLowerCase())">
                    @csrf
                    <input type="hidden" name="company_id" value="{{ $presenca->company_id }}">

                    <button type="submit"
                            @class([
                                'flex w-full items-center gap-4 rounded-2xl border-2 px-5 py-5 text-left transition',
                                'border-success-400 bg-success-50' => $presenca->fezCheckin(),
                                'border-gray-200 bg-white hover:border-brand-300' => ! $presenca->fezCheckin(),
                            ])>
                        <span @class([
                            'grid h-12 w-12 shrink-0 place-items-center rounded-full text-xl font-bold',
                            'bg-success-500 text-white' => $presenca->fezCheckin(),
                            'bg-gray-100 text-gray-400' => ! $presenca->fezCheckin(),
                        ])>
                            {{ $presenca->fezCheckin() ? '✓' : Str::upper(Str::substr($presenca->company?->name ?? '?', 0, 1)) }}
                        </span>

                        <span class="min-w-0 flex-1">
                            <strong class="block truncate text-lg font-semibold text-gray-900">{{ $presenca->company?->name }}</strong>
                            <span class="block text-sm {{ $presenca->fezCheckin() ? 'text-success-700' : 'text-gray-400' }}">
                                {{ $presenca->fezCheckin()
                                    ? 'chegou às '.$presenca->checked_in_at->format('H:i').' · '.$presenca->checkinLabel()
                                    : 'toque para marcar a chegada' }}
                            </span>
                        </span>
                    </button>
                </form>
            @endforeach
        </div>

        <p class="mt-8 text-center text-xs text-gray-400">
            A tela se atualiza sozinha a cada 20 segundos para mostrar quem fez check-in pelo celular.
            <a href="{{ route('admin.reunioes.show', $reuniao) }}" class="ml-1 font-medium text-brand-600 hover:underline">Voltar ao painel</a>
        </p>
    </main>
</body>
</html>
