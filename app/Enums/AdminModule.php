<?php

namespace App\Enums;

/**
 * Modulos do painel administrativo que podem ser liberados por usuario.
 *
 * Dashboard e perfil ficam de fora de proposito: sao o minimo que todo usuario
 * do painel precisa enxergar depois de entrar.
 */
enum AdminModule: string
{
    case Crm = 'crm';
    case Members = 'members';
    case Companies = 'companies';
    case Jobs = 'jobs';
    case Columns = 'columns';
    case Forum = 'forum';
    case Meetings = 'meetings';
    case Minutes = 'minutes';
    case Specialties = 'specialties';
    case Industries = 'industries';
    case Radar = 'radar';
    case Cms = 'cms';
    case Users = 'users';

    public function label(): string
    {
        return match ($this) {
            self::Crm => 'Novos associados',
            self::Members => 'Membros',
            self::Companies => 'Empresas',
            self::Jobs => 'Vagas',
            self::Columns => 'Colunas',
            self::Forum => 'Fórum',
            self::Meetings => 'Reuniões',
            self::Minutes => 'Atas de reunião',
            self::Specialties => 'Especialidades',
            self::Industries => 'Indústrias',
            self::Radar => 'Radar',
            self::Cms => 'Site / CMS',
            self::Users => 'Usuários',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Crm => 'Funil de quem está sendo convidado a entrar, com próximo passo e histórico.',
            self::Members => 'Cadastro, aprovação e lixeira de membros.',
            self::Companies => 'Cadastro, aprovação, convites e lixeira de empresas.',
            self::Jobs => 'Aprovação das vagas publicadas pelas empresas.',
            self::Columns => 'Colunistas e aprovação das colunas que eles escrevem.',
            self::Forum => 'Assuntos do fórum e moderação dos tópicos dos associados.',
            self::Meetings => 'Agenda de reuniões, presenças e encaminhamentos.',
            self::Minutes => 'Criação e download das atas.',
            self::Specialties => 'Lista de especialidades usada nos filtros.',
            self::Industries => 'Cadastro das indústrias da região e o cruzamento com o que as associadas fazem.',
            self::Radar => 'Fila do que a varredura automática encontrou sobre tecnologia na região.',
            self::Cms => 'Notícias, eventos, páginas e configurações do site.',
            self::Users => 'Criar usuários do painel e definir permissões.',
        };
    }

    /**
     * Conceder "Usuários" é conceder o painel inteiro por tabela: quem edita
     * usuários pode se autopromover. O formulário avisa em vez de esconder.
     */
    public function isSensitive(): bool
    {
        return $this === self::Users;
    }

    /**
     * @return array<int, self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * @param  iterable<mixed>  $values
     * @return array<int, string>
     */
    public static function sanitize(iterable $values): array
    {
        $valid = [];

        foreach ($values as $value) {
            if (is_string($value) && ($module = self::tryFrom($value))) {
                $valid[$module->value] = true;
            }
        }

        return array_keys($valid);
    }
}
