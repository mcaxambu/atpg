<?php

namespace App\Support;

use App\Enums\AttendanceStatus;
use App\Models\Company;
use App\Models\Member;
use App\Models\MeetingAttendance;
use App\Models\User;

/**
 * Quanto do caminho de entrada o novo associado ja andou.
 *
 * NENHUM passo aqui e marcado a mao. Cada um e uma pergunta que o proprio
 * banco responde — entrou no painel? mandou o logo? tem colaborador? Checklist
 * que alguem precisa ir marcando dura duas semanas e depois mente: fica cheio
 * de item marcado que nao aconteceu e item feito que ninguem marcou.
 *
 * Por isso, quando o acompanhamento diz que falta algo, falta de verdade.
 */
class OnboardingProgress
{
    /**
     * Desde quando o portal grava a data de acesso (`users.last_login_at`).
     *
     * Antes disso ninguem tem data, inclusive quem usava o painel todo dia.
     * Sem esta linha de corte, o primeiro acompanhamento acusaria as 28
     * empresas de "nunca entraram" — e um relatorio que mente na estreia nao
     * e levado a serio depois.
     */
    private const RASTREIO_DE_ACESSO_DESDE = '2026-09-29';

    /** @var array<int, array{chave: string, titulo: string, ajuda: string, feito: bool, indefinido: bool}> */
    public readonly array $passos;

    public function __construct(public readonly Company|Member $associado)
    {
        $this->passos = $associado instanceof Company
            ? $this->passosDaEmpresa($associado)
            : $this->passosDoMembro($associado);
    }

    /**
     * O que falta de verdade.
     *
     * Passo "indefinido" (sem dado para responder) nao entra: cobrar alguem
     * por algo que o sistema nao sabe medir gasta a credibilidade do aviso.
     *
     * @return array<int, array{chave: string, titulo: string, ajuda: string, feito: bool, indefinido: bool}>
     */
    public function pendentes(): array
    {
        return array_values(array_filter($this->passos, fn (array $p) => ! $p['feito'] && ! $p['indefinido']));
    }

    public function concluidos(): int
    {
        return count(array_filter($this->passos, fn (array $p) => $p['feito']));
    }

    /** Passos que contam: os indefinidos ficam de fora da conta. */
    public function total(): int
    {
        return count(array_filter($this->passos, fn (array $p) => ! $p['indefinido']));
    }

    public function completo(): bool
    {
        return $this->pendentes() === [];
    }

    public function percentual(): int
    {
        return $this->total() === 0 ? 100 : (int) round($this->concluidos() / $this->total() * 100);
    }

    /** Dias desde a aprovacao — quanto tempo o associado esta assim. */
    public function diasDesdeAprovacao(): int
    {
        $marco = $this->associado->reviewed_at ?? $this->associado->created_at;

        return $marco ? (int) $marco->diffInDays(now()) : 0;
    }

    private function usuario(): ?User
    {
        return $this->associado instanceof Company
            ? User::where('company_id', $this->associado->id)->first()
            : User::where('member_id', $this->associado->id)->first();
    }

    /** @return array<int, array{chave: string, titulo: string, ajuda: string, feito: bool}> */
    private function passosDaEmpresa(Company $empresa): array
    {
        $usuario = $this->usuario();

        return [
            $this->passo('acesso', 'Acesso ao painel criado', 'O convite de acesso é enviado na aprovação.', $usuario !== null),
            $this->passoDoAcesso($usuario, 'Ninguém da empresa abriu o painel ainda.'),
            $this->passo('descricao', 'Descrição preenchida', 'O perfil no diretório fica vazio sem ela.', filled($empresa->description)),
            $this->passo('logo', 'Logo enviado', 'Sem logo, o cartão mostra só as iniciais.', filled($empresa->logo_path)),
            $this->passo('colaboradores', 'Colaboradores vinculados', 'Ninguém da equipe aparece no diretório de membros.', $empresa->members()->count() > 0),
            $this->passo('reuniao', 'Respondeu a uma convocação', 'Ainda não respondeu nenhuma convocação de reunião.', $this->respondeuConvocacao($empresa)),
        ];
    }

    /** @return array<int, array{chave: string, titulo: string, ajuda: string, feito: bool}> */
    private function passosDoMembro(Member $membro): array
    {
        $usuario = $this->usuario();

        return [
            $this->passo('acesso', 'Acesso ao painel criado', 'Membro sem acesso não consegue manter o próprio perfil.', $usuario !== null),
            $this->passoDoAcesso($usuario, 'Não abriu o painel ainda.'),
            $this->passo('resumo', 'Resumo profissional preenchido', 'O perfil público fica sem apresentação.', filled($membro->summary)),
            $this->passo('foto', 'Foto enviada', 'Sem foto, o perfil mostra só as iniciais.', filled($membro->photo_path)),
            $this->passo('especialidades', 'Especialidades escolhidas', 'Sem elas, o membro não aparece nos filtros do diretório.', $membro->specialties()->count() > 0),
        ];
    }

    /**
     * Presenca respondida (confirmada ou recusada) em alguma reuniao.
     *
     * Vale a RESPOSTA, e nao a presenca: quem avisou que nao ia ja esta
     * acompanhando a associacao, que e o que este passo quer medir.
     */
    private function respondeuConvocacao(Company $empresa): bool
    {
        return MeetingAttendance::query()
            ->where('company_id', $empresa->id)
            ->where('status', '!=', AttendanceStatus::Pendente->value)
            ->exists();
    }

    /**
     * "Entrou no painel", com a ressalva de quem e mais antigo que o registro.
     *
     * Para um acesso criado antes de `RASTREIO_DE_ACESSO_DESDE` a resposta
     * honesta e "nao sei": a pessoa pode usar o painel ha meses. Fica
     * indefinido ate ela entrar de novo, e ai vira um sim de verdade.
     */
    private function passoDoAcesso(?User $usuario, string $ajuda): array
    {
        if ($usuario === null) {
            return $this->passo('entrou', 'Entrou no painel', $ajuda, false);
        }

        if ($usuario->last_login_at !== null) {
            return $this->passo('entrou', 'Entrou no painel', $ajuda, true);
        }

        $anteriorAoRegistro = $usuario->created_at?->lt(self::RASTREIO_DE_ACESSO_DESDE) ?? false;

        return $this->passo(
            'entrou',
            'Entrou no painel',
            $anteriorAoRegistro ? 'Acesso criado antes de o portal registrar entradas — sem dado.' : $ajuda,
            false,
            indefinido: $anteriorAoRegistro
        );
    }

    /** @return array{chave: string, titulo: string, ajuda: string, feito: bool, indefinido: bool} */
    private function passo(string $chave, string $titulo, string $ajuda, bool $feito, bool $indefinido = false): array
    {
        return compact('chave', 'titulo', 'ajuda', 'feito', 'indefinido');
    }
}
