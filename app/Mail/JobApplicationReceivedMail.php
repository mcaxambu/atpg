<?php

namespace App\Mail;

use App\Models\JobApplication;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Avisa a empresa que chegou uma candidatura.
 *
 * O e-mail leva o nome de quem se candidatou e o link do painel — nada mais.
 * Contato e curriculo ficam onde ha controle de acesso; e-mail e canal aberto,
 * e nao ha por que espalhar dado pessoal de terceiro por ele.
 */
class JobApplicationReceivedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public JobApplication $application,
        public string $recipientName,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = SiteSetting::getValue('site_name', 'Associação Tech PG');
        $titulo = $this->application->jobOpening?->title;

        return new Envelope(subject: "Nova candidatura para \"{$titulo}\" - {$siteName}");
    }

    public function content(): Content
    {
        $job = $this->application->jobOpening;

        return new Content(
            markdown: 'emails.job-application-received',
            with: [
                'siteName' => SiteSetting::getValue('site_name', 'Associação Tech PG'),
                'supportEmail' => SiteSetting::getValue('email'),
                'job' => $job,
                'total' => $job?->applications()->count() ?? 0,
                'panelUrl' => $job ? route('empresa.vagas.candidaturas', $job->id) : route('empresa.vagas.index'),
            ],
        );
    }
}
