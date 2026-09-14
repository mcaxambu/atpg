<?php

namespace App\Http\Middleware;

use App\Enums\AdminModule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe um grupo de rotas do painel a um modulo liberado para o usuario.
 *
 * Roda depois de "role:admin", entao aqui o usuario ja e da diretoria: o que
 * falta checar e se aquele modulo esta na lista dele. Diferente do papel, aqui
 * o 403 e proposital — nao ha outro painel para onde mandar.
 */
class EnsureUserCanAccessModule
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        if (! $user || ! AdminModule::tryFrom($module)) {
            abort(403);
        }

        if (! $user->canAccessModule($module)) {
            abort(403, 'Seu acesso não inclui este módulo. Fale com a diretoria.');
        }

        return $next($request);
    }
}
