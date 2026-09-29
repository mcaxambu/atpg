<?php

namespace App\Enums;

/**
 * De onde veio o prospecto.
 *
 * Serve para a diretoria saber o que funciona: se a maioria dos associados
 * chega por indicacao, evento vale menos esforco — e vice-versa.
 */
enum ProspectSource: string
{
    case Indicacao = 'indicacao';
    case Evento = 'evento';
    case Prospeccao = 'prospeccao';
    case Site = 'site';
    case Redes = 'redes';
    case Outro = 'outro';

    public function label(): string
    {
        return match ($this) {
            self::Indicacao => 'Indicação',
            self::Evento => 'Evento',
            self::Prospeccao => 'Prospecção ativa',
            self::Site => 'Procurou pelo site',
            self::Redes => 'Redes sociais',
            self::Outro => 'Outro',
        };
    }

    /** @return array<int, self> */
    public static function all(): array
    {
        return self::cases();
    }
}
