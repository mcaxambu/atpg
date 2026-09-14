<?php

namespace App\Enums;

enum JobWorkplace: string
{
    case OnSite = 'presencial';
    case Hybrid = 'hibrido';
    case Remote = 'remoto';

    public function label(): string
    {
        return match ($this) {
            self::OnSite => 'Presencial',
            self::Hybrid => 'Híbrido',
            self::Remote => 'Remoto',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $place) => [$place->value => $place->label()])
            ->all();
    }
}
