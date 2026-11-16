<?php

namespace App\Enums;

/**
 * O que o radar achou que o item e.
 *
 * `Irrelevante` existe para a leitura automatica poder dizer "isto nao e
 * assunto nosso" com todas as letras: sem essa opcao, um modelo pressionado a
 * escolher entre evento, noticia e edital transforma qualquer texto em um dos
 * tres.
 */
enum RadarTipo: string
{
    case Evento = 'evento';
    case Noticia = 'noticia';
    case Edital = 'edital';
    case Irrelevante = 'irrelevante';

    public function label(): string
    {
        return match ($this) {
            self::Evento => 'Evento',
            self::Noticia => 'Notícia',
            self::Edital => 'Edital',
            self::Irrelevante => 'Fora do assunto',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Evento => 'bg-brand-50 text-brand-700 dark:bg-brand-500/15 dark:text-brand-300',
            self::Noticia => 'bg-success-50 text-success-700 dark:bg-success-500/15 dark:text-success-300',
            self::Edital => 'bg-warning-50 text-warning-700 dark:bg-warning-500/15 dark:text-warning-300',
            self::Irrelevante => 'bg-gray-100 text-gray-500 dark:bg-white/[0.06] dark:text-gray-400',
        };
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
