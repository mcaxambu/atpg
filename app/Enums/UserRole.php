<?php

namespace App\Enums;

enum UserRole: string
{
    /** Equipe da associacao: acesso total ao painel administrativo. */
    case Admin = 'admin';

    /** Responsavel por uma empresa associada: acesso apenas aos dados dela. */
    case Company = 'company';

    /** Profissional associado: acesso apenas ao proprio cadastro. */
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Diretoria (painel administrativo)',
            self::Company => 'Empresa associada',
            self::Member => 'Membro',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Acessa o painel da associação. Pode ter acesso total ou apenas aos módulos liberados.',
            self::Company => 'Acessa somente os dados da empresa vinculada e os colaboradores dela.',
            self::Member => 'Acessa somente o próprio cadastro no diretório.',
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
