<?php

namespace App\Actions;

use App\Enums\ProspectStage;
use App\Mail\RegistrationStatusMail;
use App\Models\Company;
use App\Models\Member;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Concentra as decisoes de moderacao para que empresa e membro sigam
 * exatamente o mesmo fluxo, incluindo o aviso por e-mail ao interessado.
 */
class ModerateRegistration
{
    public function __construct(private readonly ProvisionCompanyAccess $provisionAccess) {}

    /**
     * @return array{status: string, user: ?User}|null resultado do provisionamento,
     *                                                 para que a tela possa avisar quando o convite nao saiu
     */
    public function approve(Model $subject, ?User $reviewer = null): ?array
    {
        $subject->approve($reviewer);

        $this->notify($subject, RegistrationStatusMail::APPROVED);

        // Fecha o funil: o prospecto que trouxe este cadastro vira associado.
        // Aqui e o unico ponto por onde toda aprovacao passa, entao e o unico
        // lugar onde isso nao depende de alguem lembrar.
        $this->encerrarProspecto($subject, $reviewer);

        // Empresa aprovada ganha acesso ao proprio painel.
        if ($subject instanceof Company) {
            return ($this->provisionAccess)($subject);
        }

        return null;
    }

    /**
     * Move para "Associado" o prospecto ligado a este cadastro.
     *
     * Falha aqui nao pode derrubar a aprovacao: o cadastro ja foi aprovado e o
     * e-mail ja saiu. O funil e assunto interno.
     */
    private function encerrarProspecto(Model $subject, ?User $reviewer): void
    {
        try {
            $chave = $subject instanceof Company ? 'company_id' : 'member_id';

            Prospect::where($chave, $subject->getKey())
                ->get()
                ->each(fn (Prospect $prospecto) => $prospecto->stage->emAndamento()
                    ? $prospecto->moverPara(ProspectStage::Associado, $reviewer)
                    : null);
        } catch (\Throwable $erro) {
            Log::warning('Não foi possível fechar o prospecto na aprovação.', ['erro' => $erro->getMessage()]);
        }
    }

    public function reject(Model $subject, ?string $reason, ?User $reviewer = null): void
    {
        $subject->reject($reason, $reviewer);

        $this->notify($subject, RegistrationStatusMail::REJECTED);
    }

    public function acknowledge(Model $subject): void
    {
        $this->notify($subject, RegistrationStatusMail::RECEIVED);
    }

    private function notify(Model $subject, string $event): void
    {
        if (! filled($subject->email)) {
            return;
        }

        $mail = new RegistrationStatusMail(
            event: $event,
            recipientName: $subject->contact_name ?: $subject->name,
            kind: $this->kind($subject),
            publicUrl: $event === RegistrationStatusMail::APPROVED ? $this->publicUrl($subject) : null,
            rejectionReason: $subject->rejection_reason,
        );

        // Um e-mail que falha nao pode derrubar a aprovacao ja gravada no banco.
        try {
            Mail::to($subject->email)->send($mail);
        } catch (\Throwable $exception) {
            Log::error('Falha ao enviar e-mail de moderacao.', [
                'subject' => $subject::class,
                'id' => $subject->getKey(),
                'event' => $event,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function kind(Model $subject): string
    {
        return $subject instanceof Company ? 'empresa' : 'membro';
    }

    private function publicUrl(Model $subject): ?string
    {
        return match (true) {
            $subject instanceof Company => route('companies.show', $subject),
            $subject instanceof Member => route('members.show', $subject),
            default => null,
        };
    }
}
