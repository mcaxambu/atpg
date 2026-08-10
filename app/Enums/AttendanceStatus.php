<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Pendente = 'pendente';
    case Confirmado = 'confirmado';
    case Talvez = 'talvez';
    case Recusado = 'recusado';

    public function label(): string
    {
        return match ($this) {
            self::Pendente => 'Sem resposta',
            self::Confirmado => 'Vai participar',
            self::Talvez => 'Talvez',
            self::Recusado => 'Não vai participar',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pendente => 'bg-gray-100 text-gray-600 dark:bg-white/[0.06] dark:text-gray-300',
            self::Confirmado => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-300',
            self::Talvez => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-300',
            self::Recusado => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-300',
        };
    }

    /**
     * Respostas que a empresa pode dar na convocacao. "Pendente" fica de fora:
     * e o estado inicial, nao uma escolha.
     *
     * @return array<string, string>
     */
    public static function replyOptions(): array
    {
        return collect([self::Confirmado, self::Talvez, self::Recusado])
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
