{{--
    Cartaz do check-in — para imprimir e plastificar.

    O QR aponta para um endereço FIXO (/checkin), e não para uma reunião: o
    cartaz é permanente, então precisa valer para toda reunião que vier. Quem
    descobre qual é a reunião do momento é o portal.

    Por isso o cartaz traz o espaço do código: o papel leva à tela certa, o
    código mostrado na sala é que confirma a presença. Um QR plastificado,
    fotografado uma vez, serviria para sempre e de qualquer lugar.
--}}
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Cartaz de check-in | Associação Tech PG</title>
    @vite(['resources/css/app.css', 'resources/js/qrcode.js'])

    <style>
        /* O cartaz é feito para o papel: na impressão some tudo que é da tela. */
        @media print {
            .nao-imprimir { display: none !important; }
            body { background: #fff; }
            .cartaz { box-shadow: none; border: 0; margin: 0; padding: 0; }
            @page { margin: 12mm; }
        }
    </style>
</head>
<body class="bg-gray-100 p-6">

    <div class="nao-imprimir mx-auto mb-6 max-w-2xl rounded-xl border border-gray-200 bg-white p-5">
        <h1 class="text-lg font-bold text-gray-900">Cartaz de check-in</h1>
        <p class="mt-1 text-sm text-gray-600">
            Imprima, plastifique e deixe na mesa da entrada. Ele vale para <strong>todas</strong> as reuniões —
            não precisa reimprimir. Em cada reunião, escreva o código do dia no espaço indicado
            (caneta de quadro branco apaga do plástico) ou deixe o código à vista no telão.
        </p>

        <div class="mt-4 flex flex-wrap gap-3">
            <button onclick="window.print()"
                    class="rounded-lg bg-brand-500 px-4 py-2.5 text-sm font-medium text-white hover:bg-brand-600">
                Imprimir
            </button>
            <a href="{{ route('admin.reunioes.index') }}"
               class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Voltar
            </a>
        </div>
    </div>

    <div class="cartaz mx-auto max-w-2xl rounded-3xl border border-gray-200 bg-white px-10 py-12 text-center shadow-sm">
        <x-brand-logo class="mx-auto h-16" />

        <h2 class="mt-8 text-4xl font-black leading-tight text-gray-900">
            Registre sua presença
        </h2>

        <p class="mt-3 text-lg text-gray-600">
            Aponte a câmera do celular para o código abaixo.
        </p>

        <div class="mt-8 inline-block rounded-2xl border-4 border-gray-900 p-4">
            <canvas data-qr="{{ $url }}" data-qr-size="340"></canvas>
        </div>

        <p class="mt-6 text-base text-gray-500">
            Ou abra no navegador:<br>
            <strong class="text-xl font-bold text-gray-900">{{ $urlCurta }}</strong>
        </p>

        <div class="mt-10 rounded-2xl bg-gray-50 px-6 py-6">
            <span class="text-sm font-semibold uppercase tracking-wider text-gray-500">Código desta reunião</span>
            <div class="mx-auto mt-3 h-16 w-64 rounded-xl border-2 border-dashed border-gray-400"></div>
            <p class="mt-3 text-sm text-gray-500">
                A diretoria escreve aqui ou mostra no telão.
            </p>
        </div>

        <p class="mt-8 text-sm text-gray-400">
            É preciso estar com o acesso do portal no celular. Sem acesso? Fale com a diretoria.
        </p>
    </div>
</body>
</html>
