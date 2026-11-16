{{-- Comprovante para imprimir ou salvar em PDF pelo próprio navegador. --}}
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Recibo {{ $mensalidade->competencia->format('m/Y') }} | {{ $mensalidade->company?->name }}</title>
    @vite(['resources/css/app.css'])

    <style>
        @media print {
            .nao-imprimir { display: none !important; }
            body { background: #fff; }
        }
    </style>
</head>
<body class="bg-gray-100 p-6 text-gray-800">
    <div class="mx-auto max-w-xl">
        <div class="nao-imprimir mb-4 flex justify-between">
            <a href="{{ route('empresa.mensalidades.index') }}" class="text-sm text-gray-600 hover:underline">Voltar</a>
            <button onclick="window.print()" class="rounded-lg bg-brand-500 px-4 py-2 text-sm font-semibold text-white">Imprimir</button>
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white p-8">
            <h1 class="text-lg font-bold">Recibo de mensalidade</h1>
            <p class="mt-1 text-sm text-gray-500">{{ \App\Models\SiteSetting::getValue('site_name', 'Associação Tech PG') }}</p>

            <dl class="mt-6 space-y-3 text-sm">
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <dt class="text-gray-500">Associada</dt>
                    <dd class="font-medium">{{ $mensalidade->company?->name }}</dd>
                </div>
                @if ($mensalidade->company?->cnpj)
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <dt class="text-gray-500">CNPJ</dt>
                        <dd class="font-medium">{{ $mensalidade->company->cnpj }}</dd>
                    </div>
                @endif
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <dt class="text-gray-500">Competência</dt>
                    <dd class="font-medium">{{ $mensalidade->competenciaLabel() }}</dd>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-2">
                    <dt class="text-gray-500">Pago em</dt>
                    <dd class="font-medium">{{ $mensalidade->pago_em?->format('d/m/Y') }}</dd>
                </div>
                @if ($mensalidade->formaLabel())
                    <div class="flex justify-between border-b border-gray-100 pb-2">
                        <dt class="text-gray-500">Forma</dt>
                        <dd class="font-medium">{{ $mensalidade->formaLabel() }}</dd>
                    </div>
                @endif
                <div class="flex justify-between pt-2 text-base">
                    <dt class="font-semibold">Valor</dt>
                    <dd class="font-bold">R$ {{ number_format((float) $mensalidade->valor, 2, ',', '.') }}</dd>
                </div>
            </dl>

            <p class="mt-8 text-xs text-gray-400">
                Emitido em {{ now()->format('d/m/Y \à\s H:i') }}. Documento gerado pelo portal da associação.
            </p>
        </div>
    </div>
</body>
</html>
