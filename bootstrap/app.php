<?php

use App\Http\Middleware\EnsureUserCanAccessModule;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\ProtectPublicForm;
use App\Http\Middleware\SetPortalUrlRoot;
use App\Support\PanelRedirect;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Atras da Cloudflare, o Apache so ve o IP e o esquema do proxy. Sem
        // confiar nos cabecalhos X-Forwarded-*, o Laravel gera URLs http://
        // numa pagina servida por https e nao enxerga o IP real do visitante
        // (o que quebraria o throttle dos formularios publicos).
        //
        // Confiamos apenas nas faixas da Cloudflare, e nao em '*': a origem
        // continua acessivel direto pelo IP, entao qualquer um poderia forjar
        // os cabecalhos. Lista oficial em https://www.cloudflare.com/ips/
        $middleware->trustProxies(at: [
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22',
            '103.31.4.0/22', '141.101.64.0/18', '108.162.192.0/18',
            '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22',
            '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
            '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32',
            '2405:b500::/32', '2405:8100::/32', '2a06:98c0::/29',
            '2c0f:f248::/32',
        ]);

        // ForceHttps antes de tudo: nao ha razao para processar sessao,
        // CSRF ou rota numa requisicao que sera redirecionada.
        $middleware->prependToGroup('web', SetPortalUrlRoot::class);
        $middleware->prependToGroup('web', ForceHttps::class);

        $middleware->alias([
            'public-form' => ProtectPublicForm::class,
            'role' => EnsureUserHasRole::class,
            'module' => EnsureUserCanAccessModule::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('entrar'));

        // Quem ja esta autenticado e tenta abrir /entrar vai para o painel
        // dele. Sem isto o middleware "guest" jogava a pessoa na home, e o
        // clique em "Entrar" parecia nao fazer nada.
        $middleware->redirectUsersTo(fn (Request $request) => PanelRedirect::homeFor($request->user()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Erro em JSON so onde quem chama espera JSON.
         *
         * Alem da API, entra aqui o envio de imagem do editor de textos: ele e
         * chamado por `fetch` de dentro do painel, e sem isto uma sessao
         * expirada ou um arquivo recusado devolviam a PAGINA de login ou de
         * erro. O editor nao tem como ler isso e mostrava "nao foi possivel
         * enviar" para qualquer causa, escondendo o motivo real de quem esta
         * escrevendo. O caminho aparece duas vezes porque o portal tambem
         * responde sob o prefixo /atpg.
         */
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->is('painel/editor/*')
                || $request->is('atpg/painel/editor/*'),
        );
    })->create();
