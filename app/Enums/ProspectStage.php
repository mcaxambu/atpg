<?php

namespace App\Enums;

/**
 * Etapas do funil de novos associados.
 *
 * Sao poucas de proposito. Funil com dez etapas parece organizado no desenho e
 * envelhece mal no uso: ninguem lembra a diferenca entre duas etapas parecidas,
 * e o cartao fica parado na errada. Cada etapa aqui corresponde a um fato
 * verificavel — falei, conversei, convidei, se cadastrou, entrou.
 */
enum ProspectStage: string
{
    case Novo = 'novo';
    case Contato = 'contato';
    case Reuniao = 'reuniao';
    case Convite = 'convite';
    case Cadastro = 'cadastro';
    case Associado = 'associado';
    case Perdido = 'perdido';

    public function label(): string
    {
        return match ($this) {
            self::Novo => 'Novo',
            self::Contato => 'Em contato',
            self::Reuniao => 'Reunião',
            self::Convite => 'Convite enviado',
            self::Cadastro => 'Cadastro recebido',
            self::Associado => 'Associado',
            self::Perdido => 'Perdido',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Novo => 'Identificado, ainda sem contato.',
            self::Contato => 'Já falamos; conversa em andamento.',
            self::Reuniao => 'Reunião marcada ou realizada.',
            self::Convite => 'Convite para se associar enviado.',
            self::Cadastro => 'Preencheu o cadastro; aguarda aprovação.',
            self::Associado => 'Aprovado e dentro da associação.',
            self::Perdido => 'Não seguiu adiante.',
        };
    }

    /** Cor do cartao no funil, no padrao de cores do painel. */
    public function color(): string
    {
        return match ($this) {
            self::Novo => 'gray',
            self::Contato => 'blue-light',
            self::Reuniao => 'brand',
            self::Convite => 'orange',
            self::Cadastro => 'warning',
            self::Associado => 'success',
            self::Perdido => 'error',
        };
    }

    /**
     * Etapas que aparecem como coluna no funil.
     *
     * "Associado" e "Perdido" ficam de fora: sao o fim da linha, e manter as
     * duas no quadro faria a tela crescer para sempre. Quem quiser ver o que
     * terminou usa o filtro.
     */
    public static function funil(): array
    {
        return [self::Novo, self::Contato, self::Reuniao, self::Convite, self::Cadastro];
    }

    /** Ainda em andamento: nem virou associado nem foi perdido. */
    public function emAndamento(): bool
    {
        return ! in_array($this, [self::Associado, self::Perdido], true);
    }

    /** @return array<int, self> */
    public static function all(): array
    {
        return self::cases();
    }
}
