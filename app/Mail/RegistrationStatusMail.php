<?php

namespace App\Mail;

use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * E-mail transacional do fluxo de moderacao (recebido / aprovado / rejeitado),
 * usado tanto para empresas quanto para membros.
 */
class RegistrationStatusMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public const RECEIVED = 'received';

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public function __construct(
        public string $event,
        public string $recipientName,
        public string $kind,
        public ?string $publicUrl = null,
        public ?string $rejectionReason = null,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = SiteSetting::getValue('site_name', 'Associação Tech PG');

        $subject = match ($this->event) {
            self::RECEIVED => "Recebemos seu cadastro de {$this->kind} - {$siteName}",
            self::APPROVED => "Seu cadastro de {$this->kind} foi aprovado - {$siteName}",
            self::REJECTED => "Sobre seu cadastro de {$this->kind} - {$siteName}",
            default => $siteName,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.registration-status',
            with: [
                'siteName' => SiteSetting::getValue('site_name', 'Associação Tech PG'),
                'supportEmail' => SiteSetting::getValue('email'),
            ],
        );
    }
}
