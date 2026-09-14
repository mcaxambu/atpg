<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Support\PanelRedirect;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe um grupo de rotas a um papel. Quem tem outro papel nao leva 403:
 * e mandado para o painel dele, que e o comportamento util para quem digitou
 * uma URL do painel errado.
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        $expected = UserRole::tryFrom($role);

        if (! $user || ! $expected) {
            abort(403);
        }

        if ($user->role !== $expected) {
            return redirect()->to(PanelRedirect::homeFor($user));
        }

        // Empresa sem vinculo nao tem o que gerenciar.
        if ($user->isCompany() && ! $user->company) {
            abort(403, 'Seu acesso não está vinculado a nenhuma empresa.');
        }

        // Membro sem cadastro no diretorio idem.
        if ($user->isMember() && ! $user->member) {
            abort(403, 'Seu acesso não está vinculado a nenhum cadastro.');
        }

        return $next($request);
    }
}
