<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redireciona HTTP para HTTPS em producao.
 *
 * Nao entra em laco atras da Cloudflare porque o SetPortalUrlRoot/TrustProxies
 * ja faz o Laravel enxergar o esquema real do visitante pelo X-Forwarded-Proto:
 *
 *   visitante https -> CF -> origem http (XFP: https)  => isSecure() true, sem redirect
 *   visitante http  -> CF -> origem http (XFP: http)   => redireciona uma vez
 *
 * O ideal e que o "Always Use HTTPS" da Cloudflare resolva isso na borda, sem
 * a requisicao chegar ate aqui; isto e a rede de seguranca.
 */
class ForceHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldRedirect($request)) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }

    private function shouldRedirect(Request $request): bool
    {
        if ($request->secure() || ! app()->isProduction()) {
            return false;
        }

        // O Let's Encrypt valida a renovacao por aqui; redirecionar quebraria
        // a renovacao automatica a cada 90 dias.
        if ($request->is('.well-known/*')) {
            return false;
        }

        // Acesso por IP ou por nome interno nao tem certificado; forcar HTTPS
        // ali so quebraria o acesso direto ao servidor.
        $host = $request->getHost();

        if (filter_var($host, FILTER_VALIDATE_IP) || ! str_contains($host, '.')) {
            return false;
        }

        return true;
    }
}
