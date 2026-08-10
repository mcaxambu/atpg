<!doctype html>
<html lang="pt-BR">
<head>
    @php
        $settings = $siteSettings ?? [];
        $siteName = $settings['site_name'] ?? 'Portal Associação Tech PG';
        $siteTagline = $settings['site_tagline'] ?? 'Portal Associativo';
        $logoPath = $settings['logo_path'] ?? null;
        $faviconPath = $settings['favicon_path'] ?? null;
        $brandLogo = $logoPath ? asset('storage/' . $logoPath) : asset('images/atpg-logo.png') . '?v=' . (@filemtime(public_path('images/atpg-logo.png')) ?: time());
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Cadastro') | {{ $siteName }}</title>
    @if ($faviconPath)
        <link rel="icon" href="{{ asset('storage/' . $faviconPath) }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #0047A0;
            --brand-dark: #07306F;
            --brand-light: #0D72C7;
            --teal: #2EA343;
            --accent: #FFC100;
            --ink: #101828;
            --muted: #667085;
            --line: #d0d5dd;
            --soft-line: #e4e7ec;
            --bg: #f9fafb;
            --panel: #ffffff;
            --success: #047857;
            font-family: "Open Sans", Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 0%, rgba(0, 71, 160, .14), transparent 28%),
                radial-gradient(circle at 92% 12%, rgba(46, 163, 67, .12), transparent 26%),
                linear-gradient(180deg, #ffffff 0%, var(--bg) 42%, #eef7fb 100%);
            -webkit-font-smoothing: antialiased;
        }

        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }

        .registration-shell {
            width: min(1180px, calc(100% - 32px));
            margin: 0 auto;
            padding: clamp(24px, 5vw, 56px) 0;
        }

        .registration-card {
            overflow: hidden;
            border: 1px solid var(--soft-line);
            border-radius: 24px;
            background: var(--panel);
            box-shadow: 0 24px 80px rgba(16, 24, 40, .12);
        }

        .registration-card::before {
            content: "";
            display: block;
            height: 5px;
            background: linear-gradient(90deg, var(--brand), var(--teal), var(--accent));
        }

        .registration-top {
            display: grid;
            grid-template-columns: minmax(300px, .8fr) minmax(0, 1.2fr);
            min-height: 100%;
        }

        .registration-aside {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 36px;
            padding: clamp(28px, 5vw, 46px);
            color: #fff;
            background:
                linear-gradient(145deg, rgba(7, 48, 111, .96), rgba(0, 71, 160, .84), rgba(46, 163, 67, .50)),
                url("https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=1200&q=80") center/cover;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 46px;
            height: 46px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, .24);
            border-radius: 14px;
            background: rgba(255, 255, 255, .16);
            font-weight: 900;
        }

        .brand-mark img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 6px;
            background: #fff;
        }

        /*
         * A placa branca acompanha o tamanho da logo em vez de ter largura
         * fixa: com 286px de caixa para uma logo de ~174px sobrava um vazio
         * branco em volta. A logo e dimensionada pela ALTURA, e a largura
         * segue a proporcao dela (1100x480).
         */
        .brand-logo {
            display: inline-grid;
            place-items: center;
            width: auto;
            max-width: min(320px, 78vw);
            padding: 12px 18px;
            border: 1px solid rgba(255, 255, 255, .28);
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 18px 34px rgba(0, 24, 64, .22);
        }

        .brand-logo img {
            display: block;
            width: auto;
            height: clamp(46px, 6vw, 62px);
            max-width: 100%;
            object-fit: contain;
            padding: 0;
        }

        /* Versao branca da logo: assenta direto no fundo escuro, sem placa. */
        .brand-logo-branca {
            display: block;
            width: auto;
            height: clamp(52px, 6.5vw, 70px);
            max-width: min(320px, 78vw);
            object-fit: contain;
        }

        .brand strong,
        .brand small,
        .aside-metrics strong,
        .aside-metrics span,
        .trust-grid strong,
        .trust-grid span {
            display: block;
        }

        .brand small {
            color: rgba(255, 255, 255, .72);
        }

        .aside-copy h2 {
            margin: 0 0 14px;
            font-size: clamp(2rem, 4vw, 3.25rem);
            line-height: 1.04;
            letter-spacing: -.035em;
        }

        .aside-copy p,
        .aside-note {
            color: rgba(255, 255, 255, .78);
            line-height: 1.7;
        }

        .aside-note,
        .aside-metrics div {
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 16px;
            background: rgba(255, 255, 255, .12);
        }

        .aside-note {
            padding: 16px;
            font-size: .94rem;
        }

        /*
         * minmax(0, 1fr) em vez de 1fr em todas as grades: "1fr" equivale a
         * minmax(auto, 1fr) e nao encolhe abaixo do conteudo, entao numa tela
         * estreita a trilha empurra a largura e a pagina estoura na horizontal.
         */
        .aside-metrics {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .aside-metrics div {
            padding: 14px;
        }

        .aside-metrics strong {
            font-size: 1.35rem;
            line-height: 1;
        }

        .aside-metrics span {
            margin-top: 6px;
            color: rgba(255, 255, 255, .72);
            font-size: .76rem;
            font-weight: 800;
            letter-spacing: .05em;
            text-transform: uppercase;
        }

        .registration-main {
            padding: clamp(28px, 5vw, 46px);
        }

        .form-heading {
            margin-bottom: 26px;
        }

        .eyebrow {
            display: block;
            margin-bottom: 8px;
            color: var(--accent);
            font-size: .78rem;
            font-weight: 900;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .form-heading h1 {
            margin: 0;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.08;
            letter-spacing: -.035em;
        }

        .form-heading p {
            max-width: 680px;
            margin: 12px 0 0;
            color: var(--muted);
            line-height: 1.7;
        }

        .trust-grid,
        .form-steps,
        .company-form {
            display: grid;
            gap: 16px;
        }

        .trust-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 24px;
        }

        .trust-grid article,
        .form-steps div,
        .company-form-card {
            border: 1px solid var(--soft-line);
            border-radius: 18px;
            background: #fff;
        }

        .trust-grid article {
            padding: 16px;
            background: #f8fafc;
        }

        .trust-grid strong {
            font-size: .94rem;
        }

        .trust-grid span {
            margin-top: 5px;
            color: var(--muted);
            font-size: .82rem;
            line-height: 1.45;
        }

        .form-steps {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-bottom: 22px;
        }

        .form-steps div {
            display: flex;
            gap: 10px;
            align-items: center;
            padding: 12px;
            color: #475467;
            font-size: .86rem;
            font-weight: 800;
        }

        .form-steps span {
            display: grid;
            place-items: center;
            width: 28px;
            height: 28px;
            flex: 0 0 auto;
            border-radius: 10px;
            background: #eef6fb;
            color: var(--brand);
            font-size: .78rem;
            font-weight: 900;
        }

        .company-form-card {
            display: grid;
            gap: 20px;
            padding: clamp(18px, 3vw, 26px);
            box-shadow: 0 10px 30px rgba(16, 24, 40, .06);
        }

        .form-section-title {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 14px;
            margin-top: 4px;
            padding-top: 18px;
            border-top: 1px solid var(--soft-line);
        }

        .form-section-title:first-child {
            margin-top: 0;
            padding-top: 0;
            border-top: 0;
        }

        .form-section-title strong {
            font-size: 1.05rem;
        }

        .form-section-title span {
            color: var(--muted);
            font-size: .88rem;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        label {
            display: grid;
            gap: 8px;
            color: #344054;
            font-size: .92rem;
            font-weight: 750;
        }

        .field-hint {
            min-height: 18px;
            color: var(--muted);
            font-size: .8rem;
            font-weight: 650;
        }

        .field-hint.is-success { color: var(--success); }
        .field-hint.is-error { color: #b42318; }

        input,
        textarea {
            width: 100%;
            min-height: 48px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            color: var(--ink);
            outline: none;
            box-shadow: 0 1px 2px rgba(16, 24, 40, .04);
            transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
        }

        input[type="file"] {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f9fafb;
        }

        textarea {
            min-height: 132px;
            resize: vertical;
        }

        input:focus,
        textarea:focus {
            border-color: rgba(0, 71, 160, .40);
            box-shadow: 0 0 0 4px rgba(0, 71, 160, .12);
        }

        .logo-uploader {
            display: grid;
            grid-template-columns: 92px minmax(0, 1fr);
            gap: 14px;
            align-items: center;
            padding: 12px;
            border: 1px solid var(--line);
            border-radius: 16px;
            background: #f9fafb;
        }

        .logo-preview {
            display: grid;
            place-items: center;
            width: 92px;
            height: 92px;
            overflow: hidden;
            border: 1px solid var(--soft-line);
            border-radius: 16px;
            background: #fff;
            color: var(--brand);
            font-weight: 900;
        }

        .logo-preview img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
        }

        .remember-check,
        .consent-check {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-weight: 750;
            line-height: 1.55;
        }

        .remember-check input,
        .consent-check input {
            width: 18px;
            min-height: 18px;
            margin-top: 2px;
            accent-color: var(--brand);
        }

        .form-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            padding-top: 4px;
        }

        .primary-button,
        .secondary-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: 0 18px;
            border-radius: 12px;
            font-weight: 850;
        }

        .primary-button {
            border: 0;
            color: #fff;
            background: var(--brand);
            box-shadow: 0 12px 24px rgba(0, 71, 160, .22);
            cursor: pointer;
        }

        .primary-button:hover { background: var(--brand-dark); }

        .secondary-button {
            border: 1px solid var(--soft-line);
            background: #fff;
            color: #344054;
        }

        .registration-success {
            display: grid;
            gap: 14px;
            padding: 26px;
            border: 1px solid #bbf7d0;
            border-radius: 20px;
            background: #f0fdf4;
        }

        .registration-success strong {
            color: var(--success);
            font-size: 1.45rem;
        }

        .registration-success p {
            margin: 0;
            color: #166534;
            line-height: 1.65;
        }

        .error-message {
            margin-bottom: 18px;
            padding: 16px;
            border: 1px solid #fecaca;
            border-radius: 14px;
            background: #fef2f2;
            color: #991b1b;
            font-weight: 700;
        }

        .error-message ul { margin: 8px 0 0; padding-left: 20px; }

        @media (max-width: 920px) {
            .registration-top,
            .form-grid,
            .trust-grid,
            .form-steps {
                grid-template-columns: minmax(0, 1fr);
            }

            .registration-aside {
                min-height: 380px;
            }
        }

        @media (max-width: 560px) {
            .registration-shell {
                width: 100%;
                padding: 0;
            }

            .registration-card {
                min-height: 100vh;
                border: 0;
                border-radius: 0;
            }

            .form-actions > * {
                width: 100%;
            }

            .aside-metrics,
            .logo-uploader {
                grid-template-columns: minmax(0, 1fr);
            }
        }

        /*
         * Travas contra estouro horizontal no celular. Filho de grid/flex tem
         * min-width:auto por padrao e se recusa a encolher abaixo do proprio
         * conteudo — um e-mail ou URL longa era suficiente para alargar a
         * pagina inteira.
         */
        .registration-top > *,
        .form-grid > *,
        .trust-grid > *,
        .form-steps > *,
        .aside-metrics > *,
        .logo-uploader > * {
            min-width: 0;
        }

        .registration-aside,
        .registration-main {
            overflow-wrap: anywhere;
        }

        img {
            max-width: 100%;
        }

        input,
        select,
        textarea {
            max-width: 100%;
        }

        /* ---- Convocação de reunião ---- */
        .convocacao-tipo {
            display: inline-block;
            margin-bottom: 10px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .06em;
            text-transform: uppercase;
        }

        .convocacao-quando {
            margin-top: 18px;
            font-size: 1.02rem;
            line-height: 1.6;
        }

        .convocacao-local {
            margin-top: 10px;
            opacity: .92;
        }

        .convocacao-local a { text-decoration: underline; }

        .convocacao-pauta {
            margin-bottom: 26px;
            padding: 20px 22px;
            border: 1px solid var(--soft-line);
            border-radius: 16px;
            background: #f8fafc;
        }

        .convocacao-pauta h3 {
            margin: 0 0 14px;
            color: var(--brand);
            font-size: 1.05rem;
        }

        .convocacao-pauta ol {
            margin: 0;
            padding-left: 20px;
            display: grid;
            gap: 14px;
        }

        .convocacao-pauta li strong { display: block; }
        .convocacao-pauta li small { color: var(--muted); }
        .convocacao-pauta li p {
            margin: 6px 0 0;
            color: #475569;
            font-size: .92rem;
            line-height: 1.6;
        }

        .convocacao-form h3 {
            margin: 0 0 4px;
            color: var(--ink);
            font-size: 1.15rem;
        }

        .convocacao-resposta-atual {
            margin: 0;
            padding: 12px 14px;
            border-radius: 12px;
            background: #eef6ff;
            color: var(--brand);
            font-size: .92rem;
        }

        .convocacao-opcoes {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 16px;
            border: 1px solid var(--soft-line);
            border-radius: 14px;
        }

        .convocacao-opcoes legend {
            padding: 0 6px;
            color: var(--muted);
            font-size: .82rem;
            font-weight: 700;
        }

        .convocacao-opcoes label {
            display: flex;
            gap: 10px;
            align-items: center;
            min-height: 44px;
            cursor: pointer;
        }

        .convocacao-opcoes input { width: 18px; height: 18px; }

        .convocacao-contador {
            margin: 4px 0 0;
            color: var(--muted);
            font-size: .88rem;
        }

        /*
         * A caixa da logo tinha largura fixa de ate 286px; somada ao texto ao
         * lado, estourava a lateral no celular e cortava "Portal Associativo".
         */
        @media (max-width: 620px) {
            .brand {
                flex-wrap: wrap;
                gap: 10px;
            }

            .brand-logo {
                max-width: min(240px, 72vw);
                padding: 10px 14px;
            }

            .brand-logo img {
                height: 44px;
            }
        }
    </style>
</head>
<body>
    <main class="registration-shell">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
