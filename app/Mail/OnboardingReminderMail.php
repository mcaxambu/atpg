<?php

namespace App\Mail;

use App\Models\Company;
use App\Models\Member;
use App\Models\SiteSetting;
use App\Support\OnboardingProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Lembrete do que falta para o associado novo completar a entrada.
 *
 * O tom importa: quem recebe acabou de entrar na associacao e o e-mail nao
 * pode soar como cobranca de inadimplencia. Por isso lista o que falta como
 * ajuda, e nao como pendencia.
 */
class OnboardingReminderMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Company|Member $associado,
        public OnboardingProgress $progresso,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = SiteSetting::getValue('site_name', 'Associação Tech PG');

        return new Envelope(subject: "Faltam alguns passos no seu perfil - {$siteName}");
    }

    public function content(): Content
    {
        $ehEmpresa = $this->associado instanceof Company;

        return new Content(
            markdown: 'emails.onboarding-reminder',
            with: [
                'nome' => $this->associado->name,
                'pendentes' => $this->progresso->pendentes(),
                // O painel do membro abre direto no perfil dele; o da empresa
                // tem visão geral própria.
                'painel' => $ehEmpresa ? route('empresa.dashboard') : route('membro.perfil.edit'),
                'siteName' => SiteSetting::getValue('site_name', 'Associação Tech PG'),
            ],
        );
    }
}
