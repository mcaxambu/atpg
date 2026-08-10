<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * O portal responde na raiz e tambem sob /atpg. Quando a requisicao chega pelo
 * prefixo, forcamos a raiz das URLs geradas para que todos os links continuem
 * dentro de /atpg.
 *
 * Isto vivia no boot() do AppServiceProvider, onde dependia de um request que
 * nem sempre existe (CLI, filas, cache de rotas).
 */
class SetPortalUrlRoot
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('atpg') || $request->is('atpg/*')) {
            URL::forceRootUrl($request->getSchemeAndHttpHost().'/atpg');
        }

        return $next($request);
    }

    /**
     * forceRootUrl e estado global do container. Sem limpar ao final, ele vaza
     * para a requisicao seguinte sob Octane (e nos testes), gerando URLs
     * duplicadas do tipo /atpg/atpg/empresas.
     */
    public function terminate(Request $request, Response $response): void
    {
        URL::forceRootUrl(null);
    }
}
