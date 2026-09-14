<!doctype html>
<html lang="pt-BR">
<head>
    @php
        $settings = $siteSettings ?? [];
        $siteName = $settings['site_name'] ?? 'Portal Associação Tech PG';
        $siteTagline = $settings['site_tagline'] ?? 'Portal Associativo';
        $institutionalText = $settings['institutional_text'] ?? 'Uma base institucional para conectar talentos, empresas, eventos e iniciativas de tecnologia em Ponta Grossa.';
        $logoPath = $settings['logo_path'] ?? null;
        $faviconPath = $settings['favicon_path'] ?? null;
        $city = $settings['city'] ?? 'Ponta Grossa, PR';
        $email = $settings['email'] ?? 'contato@techpg.org.br';
        $phone = $settings['phone'] ?? '(42) 99999-2026';
        $linkedinUrl = $settings['linkedin_url'] ?? '#';
        $instagramUrl = $settings['instagram_url'] ?? '#';
        $whatsappUrl = $settings['whatsapp_url'] ?? '#';
        // Logo institucional completa (1100x480, ja contem o nome). O simbolo
        // quadrado sozinho obrigava a repetir o nome em texto ao lado.
        $brandLogo = $logoPath
            ? asset('storage/'.$logoPath)
            : asset('images/atpg-logo.png').'?v='.(@filemtime(public_path('images/atpg-logo.png')) ?: time());
    @endphp
    @php
        $metaDescription = trim($__env->yieldContent('meta_description', $institutionalText));
        $metaImage = trim($__env->yieldContent('meta_image', $brandLogo));
        $pageTitle = trim($__env->yieldContent('title', $siteName));
        $canonical = url()->current();
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }}</title>

    <meta name="description" content="{{ Str::limit(strip_tags($metaDescription), 160) }}">
    <link rel="canonical" href="{{ $canonical }}">
    <meta name="robots" content="@yield('robots', 'index, follow')">

    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags($metaDescription), 200) }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:image" content="{{ $metaImage }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ Str::limit(strip_tags($metaDescription), 200) }}">
    <meta name="twitter:image" content="{{ $metaImage }}">

    @php
        // Montado aqui porque as chaves "@context"/"@type" do schema.org
        // colidem com a sintaxe de diretivas do Blade.
        $organizationSchema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $siteName,
            'description' => Str::limit(strip_tags($institutionalText), 300),
            'url' => route('home'),
            'logo' => $brandLogo,
            'email' => $email ?: null,
            'telephone' => $phone ?: null,
            'address' => ['@type' => 'PostalAddress', 'addressLocality' => $city, 'addressCountry' => 'BR'],
            'sameAs' => array_values(array_filter([$linkedinUrl, $instagramUrl], fn ($url) => $url && $url !== '#')),
        ]);
    @endphp
    <script type="application/ld+json">
        {!! json_encode($organizationSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>
    @stack('structured_data')

    <link rel="icon" href="{{ $faviconPath ? asset('storage/'.$faviconPath) : asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800;900&family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}?v={{ @filemtime(public_path('css/portal.css')) ?: time() }}">
</head>
<body>
    <div class="ambient-bg" aria-hidden="true"></div>
    <header class="site-header" data-site-header>
        <div class="header-inner">
            <a href="{{ route('home') }}" class="brand">
                <img class="brand-logo" src="{{ $brandLogo }}" alt="{{ $siteName }}">
            </a>
            {{--
                As barras precisam de um contêiner próprio: soltas dentro do
                botão (que é flex em linha) elas ficavam lado a lado, virando
                três tracinhos em vez de um hambúrguer.
            --}}
            <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="main-navigation">
                <span class="nav-toggle-bars" aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </span>
                <strong data-nav-toggle-label>Menu</strong>
            </button>
            <nav class="nav" id="main-navigation" data-main-nav>
                <a href="{{ route('home') }}">Início</a>
                <a href="{{ route('companies.index') }}">Empresas</a>
                <a href="{{ route('join') }}">Associe-se</a>
                <a href="{{ route('projects') }}">Projetos</a>
                <a href="{{ route('vagas.index') }}">Vagas</a>
                <a href="{{ route('events') }}">Eventos</a>
                <a href="{{ route('posts.index') }}">Notícias</a>
                <a href="{{ route('colunas.index') }}">Colunas</a>
                <a href="{{ route('benefits') }}">Benefícios</a>
                @isset($menuPages)
                    @foreach ($menuPages as $menuPage)
                        <a href="{{ route('pages.show', $menuPage) }}">{{ $menuPage->title }}</a>
                    @endforeach
                @endisset
                <a href="{{ route('about') }}">Sobre</a>
                {{--
                    Para quem ja esta autenticado, "Entrar" nao faz sentido: o
                    link e protegido por "guest" e devolveria a pessoa para o
                    painel, dando a impressao de que o clique nao funcionou.
                --}}
                @auth
                    <a class="login-link" href="{{ \App\Support\PanelRedirect::homeFor(auth()->user()) }}">Meu painel</a>
                @else
                    <a class="login-link" href="{{ route('entrar') }}">Entrar</a>
                @endauth
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="footer">
        <div class="footer-grid">
            <div>
                <h2>{{ $siteName }}</h2>
                <p>{{ $institutionalText }}</p>
            </div>
            <div>
                <h3>Links úteis</h3>
                <a href="{{ route('members.index') }}">Membros</a>
                <a href="{{ route('companies.index') }}">Empresas</a>
                <a href="{{ route('join') }}">Associe-se</a>
                <a href="{{ route('projects') }}">Projetos</a>
                <a href="{{ route('vagas.index') }}">Vagas</a>
                <a href="{{ route('events') }}">Eventos</a>
                <a href="{{ route('posts.index') }}">Notícias</a>
                <a href="{{ route('colunas.index') }}">Colunas</a>
                <a href="{{ route('governance') }}">Governança</a>
                <a href="{{ route('companies.register.create') }}">Cadastro</a>
                <a href="{{ route('entrar') }}">Painel</a>
            </div>
            <div>
                <h3>Privacidade</h3>
                <a href="{{ route('lgpd') }}">LGPD</a>
                <a href="{{ route('privacy') }}">Política de Privacidade</a>
                <a href="{{ route('cookies') }}">Política de Cookies</a>
            </div>
            <div>
                <h3>Contato</h3>
                <p>{{ $city }}</p>
                @if ($email)
                    <p>{{ $email }}</p>
                @endif
                @if ($phone)
                    <p>{{ $phone }}</p>
                @endif
            </div>
            <div>
                <h3>Redes sociais</h3>
                @if ($linkedinUrl)
                    <a href="{{ $linkedinUrl }}" target="_blank" rel="noopener">LinkedIn</a>
                @endif
                @if ($whatsappUrl)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener">WhatsApp</a>
                @endif
                @if ($instagramUrl)
                    <a href="{{ $instagramUrl }}" target="_blank" rel="noopener">Instagram</a>
                @endif
            </div>
        </div>
        <div class="footer-bottom">{{ $siteName }} - portal institucional em evolução.</div>
    </footer>

    {{--
        A LGPD exige que recusar cookies não essenciais seja tão fácil quanto
        aceitar, e navegar não pode ficar condicionado ao aceite. Por isso o
        aviso não bloqueia mais a página e traz as duas opções lado a lado.
    --}}
    <div class="cookie-consent-bar" data-cookie-consent hidden>
        <div class="cookie-consent" role="region" aria-labelledby="cookie-consent-title">
            <div>
                <strong id="cookie-consent-title">Privacidade e cookies</strong>
                <p>
                    Usamos cookies essenciais para o funcionamento do portal. Cookies de medição de
                    audiência só são ativados com a sua autorização.
                </p>
            </div>
            <div class="cookie-policy-links">
                <a href="{{ route('lgpd') }}">LGPD</a>
                <a href="{{ route('privacy') }}">Privacidade</a>
                <a href="{{ route('cookies') }}">Cookies</a>
            </div>
            <div class="cookie-actions">
                <button type="button" data-cookie-decline class="cookie-decline">Recusar não essenciais</button>
                <button type="button" data-cookie-accept>Aceitar todos</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const toggle = document.querySelector('[data-nav-toggle]');
            const header = document.querySelector('[data-site-header]');
            const nav = document.querySelector('[data-main-nav]');

            if (!toggle || !header || !nav) return;

            const label = toggle.querySelector('[data-nav-toggle-label]');

            toggle.addEventListener('click', () => {
                const isOpen = header.classList.toggle('is-nav-open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

                if (label) {
                    label.textContent = isOpen ? 'Fechar' : 'Menu';
                }
            });

            nav.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => {
                    header.classList.remove('is-nav-open');
                    toggle.setAttribute('aria-expanded', 'false');

                    if (label) {
                        label.textContent = 'Menu';
                    }
                });
            });
        })();

        (() => {
            const banner = document.querySelector('[data-cookie-consent]');
            const accept = document.querySelector('[data-cookie-accept]');
            const decline = document.querySelector('[data-cookie-decline]');
            const storageKey = 'atpg_cookie_consent';

            if (!banner || !accept || !decline || localStorage.getItem(storageKey)) {
                return;
            }

            banner.hidden = false;

            const decide = (choice) => {
                localStorage.setItem(storageKey, choice);
                banner.hidden = true;
            };

            accept.addEventListener('click', () => decide('accepted'));
            decline.addEventListener('click', () => decide('declined'));
        })();
    </script>

    {{-- defer: a entrada por rolagem nao pode atrasar a primeira pintura. --}}
    <script src="{{ asset('js/motion.js') }}?v={{ @filemtime(public_path('js/motion.js')) ?: time() }}" defer></script>
</body>
</html>
