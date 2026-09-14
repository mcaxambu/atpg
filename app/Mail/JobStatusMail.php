<?php

namespace App\Mail;

use App\Models\JobOpening;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Resultado da analise de uma vaga, enviado para a empresa que a publicou.
 */
class JobStatusMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public const APPROVED = 'approved';

    public const REJECTED = 'rejected';

    public function __construct(
        public JobOpening $job,
        public string $event,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = SiteSetting::getValue('site_name', 'Associação Tech PG');

        $subject = match ($this->event) {
            self::APPROVED => "Sua vaga \"{$this->job->title}\" foi publicada - {$siteName}",
            self::REJECTED => "Sobre sua vaga \"{$this->job->title}\" - {$siteName}",
            default => $siteName,
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.job-status',
            with: [
                'siteName' => SiteSetting::getValue('site_name', 'Associação Tech PG'),
                'supportEmail' => SiteSetting::getValue('email'),
                'publicUrl' => $this->event === self::APPROVED ? route('vagas.show', $this->job) : null,
                'panelUrl' => route('empresa.vagas.index'),
            ],
        );
    }
}
