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

        /*
         * Quem e da diretoria E tem cadastro proprio alcanca os dois paineis.
         *
         * Gente da diretoria costuma ser associada tambem: o secretario tem a
         * empresa dele no portal. Com um papel por acesso, essa pessoa
         * precisava de DOIS logins, com dois e-mails — e, na pratica, ficava
         * sem conseguir cuidar da propria empresa, porque todo endereco do
         * painel da empresa a devolvia para o painel da diretoria.
         *
         * O vinculo e que manda: admin sem cadastro ligado continua sendo so
         * diretoria.
         */
        $temOutroPainel = $user->isAdmin() && match ($expected) {
            UserRole::Company => $user->company !== null,
            UserRole::Member => $user->member !== null,
            default => false,
        };

        if ($user->role !== $expected && ! $temOutroPainel) {
            return redirect()->to(PanelRedirect::homeFor($user));
        }

        // Empresa sem vinculo nao tem o que gerenciar.
        if ($expected === UserRole::Company && ! $user->company) {
            abort(403, 'Seu acesso não está vinculado a nenhuma empresa.');
        }

        // Membro sem cadastro no diretorio idem.
        if ($expected === UserRole::Member && ! $user->member) {
            abort(403, 'Seu acesso não está vinculado a nenhum cadastro.');
        }

        return $next($request);
    }
}
