<?php

namespace App\Actions;

use App\Mail\RegistrationStatusMail;
use App\Models\Company;
use App\Models\Member;
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

        // Empresa aprovada ganha acesso ao proprio painel.
        if ($subject instanceof Company) {
            return ($this->provisionAccess)($subject);
        }

        return null;
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
