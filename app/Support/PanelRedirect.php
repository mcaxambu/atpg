<?php

namespace App\Support;

use App\Models\User;

/**
 * Cada papel tem um painel proprio. Concentrado aqui para que login,
 * redefinicao de senha e o middleware de papel concordem entre si.
 */
class PanelRedirect
{
    public static function homeFor(?User $user): string
    {
        return match (true) {
            $user?->isCompany() => route('empresa.dashboard'),
            $user?->isMember() => route('membro.perfil.edit'),
            // Admin restrito a modulos: o dashboard continua liberado para todos.
            default => route('admin.dashboard'),
        };
    }
}
