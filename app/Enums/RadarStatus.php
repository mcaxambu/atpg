<?php

namespace App\Enums;

enum RadarStatus: string
{
    case Novo = 'novo';
    case Publicado = 'publicado';
    case Descartado = 'descartado';

    /** A leitura automatica nao conseguiu entender o item. */
    case Ilegivel = 'ilegivel';

    public function label(): string
    {
        return match ($this) {
            self::Novo => 'Esperando decisão',
            self::Publicado => 'Publicado',
            self::Descartado => 'Descartado',
            self::Ilegivel => 'Não consegui ler',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Novo => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
            self::Publicado => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-300',
            self::Descartado => 'bg-gray-100 text-gray-500 dark:bg-white/[0.06] dark:text-gray-400',
            self::Ilegivel => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-300',
        };
    }
}
