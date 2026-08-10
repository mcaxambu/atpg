<?php

namespace App\Enums;

enum MeetingType: string
{
    case AssembleiaOrdinaria = 'assembleia_ordinaria';
    case AssembleiaExtraordinaria = 'assembleia_extraordinaria';
    case Diretoria = 'diretoria';
    case Comite = 'comite';
    case Outra = 'outra';

    public function label(): string
    {
        return match ($this) {
            self::AssembleiaOrdinaria => 'Assembleia Geral Ordinária',
            self::AssembleiaExtraordinaria => 'Assembleia Geral Extraordinária',
            self::Diretoria => 'Reunião de Diretoria',
            self::Comite => 'Reunião de Comitê',
            self::Outra => 'Outra',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::AssembleiaOrdinaria => 'AGO',
            self::AssembleiaExtraordinaria => 'AGE',
            self::Diretoria => 'Diretoria',
            self::Comite => 'Comitê',
            self::Outra => 'Reunião',
        };
    }

    /**
     * Assembleia delibera com voto e exige quorum; as demais sao de trabalho.
     */
    public function requiresQuorum(): bool
    {
        return in_array($this, [self::AssembleiaOrdinaria, self::AssembleiaExtraordinaria], true);
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
