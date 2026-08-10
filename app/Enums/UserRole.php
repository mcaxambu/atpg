<?php

namespace App\Enums;

enum UserRole: string
{
    /** Equipe da associacao: acesso total ao painel administrativo. */
    case Admin = 'admin';

    /** Responsavel por uma empresa associada: acesso apenas aos dados dela. */
    case Company = 'company';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Company => 'Empresa',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [$role->value => $role->label()])
            ->all();
    }
}
