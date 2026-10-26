<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Em qual painel a pessoa está navegando agora.
 *
 * Desde que a diretoria pode ter cadastro próprio e entrar no painel da
 * empresa, o PAPEL do usuário deixou de responder essa pergunta: um admin
 * ligado a uma empresa navega ora num painel, ora no outro. Quem responde é o
 * endereço.
 *
 * A leitura é pelo CAMINHO, e não pelo nome da rota: o portal registra tudo
 * duas vezes (na raiz e sob /atpg) e, no segundo registro, as rotas recebem
 * nomes aleatórios para não colidir — então nome de rota não serve aqui.
 */
class PanelContext
{
    public static function noPainelDaEmpresa(Request $request): bool
    {
        return $request->is('empresa', 'empresa/*', 'atpg/empresa', 'atpg/empresa/*');
    }

    public static function noPainelDoMembro(Request $request): bool
    {
        return $request->is('membro', 'membro/*', 'atpg/membro', 'atpg/membro/*');
    }

    /** O layout da moldura em volta da tela atual. */
    public static function layout(Request $request): string
    {
        return match (true) {
            self::noPainelDaEmpresa($request) => 'layouts.company',
            self::noPainelDoMembro($request) => 'layouts.membro',
            default => 'layouts.admin',
        };
    }
}
