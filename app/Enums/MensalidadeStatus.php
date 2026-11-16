<?php

namespace App\Enums;

enum MensalidadeStatus: string
{
    case Pendente = 'pendente';
    case Paga = 'paga';
    case Isenta = 'isenta';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Em aberto',
            self::Paga => 'Paga',
            self::Isenta => 'Isenta',
            self::Cancelada => 'Cancelada',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pendente => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-300',
            self::Paga => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-300',
            self::Isenta => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
            self::Cancelada => 'bg-gray-100 text-gray-500 dark:bg-white/[0.06] dark:text-gray-400',
        };
    }

    /**
     * Entra na conta do que a associação tem a receber.
     *
     * Isenta e cancelada não entram: somá-las infla a previsão com dinheiro
     * que nunca vai chegar.
     */
    public function contaComoReceita(): bool
    {
        return $this === self::Pendente || $this === self::Paga;
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $opcoes = [];

        foreach (self::cases() as $caso) {
            $opcoes[$caso->value] = $caso->label();
        }

        return $opcoes;
    }
}
