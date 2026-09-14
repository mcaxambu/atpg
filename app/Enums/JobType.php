<?php

namespace App\Enums;

enum JobType: string
{
    case Clt = 'clt';
    case Pj = 'pj';
    case Internship = 'estagio';
    case Trainee = 'trainee';
    case Freelance = 'freelance';
    case Temporary = 'temporario';

    public function label(): string
    {
        return match ($this) {
            self::Clt => 'CLT',
            self::Pj => 'PJ',
            self::Internship => 'Estágio',
            self::Trainee => 'Trainee',
            self::Freelance => 'Freelance',
            self::Temporary => 'Temporário',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [$type->value => $type->label()])
            ->all();
    }
}
