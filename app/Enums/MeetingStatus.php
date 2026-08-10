<?php

namespace App\Enums;

enum MeetingStatus: string
{
    case Agendada = 'agendada';
    case Realizada = 'realizada';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Agendada => 'Agendada',
            self::Realizada => 'Realizada',
            self::Cancelada => 'Cancelada',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Agendada => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
            self::Realizada => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-300',
            self::Cancelada => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-300',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
