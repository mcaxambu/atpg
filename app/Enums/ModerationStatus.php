<?php

namespace App\Enums;

enum ModerationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Approved => 'Aprovado',
            self::Rejected => 'Rejeitado',
        };
    }

    /**
     * Classes utilitarias para o badge de status no painel.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-300',
            self::Approved => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-300',
            self::Rejected => 'bg-error-50 text-error-700 dark:bg-error-500/15 dark:text-error-300',
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
