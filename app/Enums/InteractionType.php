<?php

namespace App\Enums;

/**
 * Tipo de contato registrado no histórico do prospecto.
 *
 * `Sistema` nao aparece no formulario: e o que o proprio portal escreve quando
 * a etapa muda, para o historico contar a conversa inteira sem ninguem
 * precisar anotar "mudei de etapa".
 */
enum InteractionType: string
{
    case Ligacao = 'ligacao';
    case Whatsapp = 'whatsapp';
    case Email = 'email';
    case Reuniao = 'reuniao';
    case Visita = 'visita';
    case Nota = 'nota';
    case Sistema = 'sistema';

    public function label(): string
    {
        return match ($this) {
            self::Ligacao => 'Ligação',
            self::Whatsapp => 'WhatsApp',
            self::Email => 'E-mail',
            self::Reuniao => 'Reunião',
            self::Visita => 'Visita',
            self::Nota => 'Anotação',
            self::Sistema => 'Registro automático',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Ligacao, self::Whatsapp => 'inbox',
            self::Email => 'inbox',
            self::Reuniao, self::Visita => 'calendar',
            self::Nota => 'file',
            self::Sistema => 'check',
        };
    }

    /** As que a pessoa escolhe no formulario. */
    public static function manuais(): array
    {
        return array_values(array_filter(self::cases(), fn (self $t) => $t !== self::Sistema));
    }
}
