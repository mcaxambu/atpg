<?php

namespace App\Mail;

use App\Models\Prospect;
use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Convite para o prospecto preencher o cadastro.
 *
 * O link leva ao mesmo formulario publico de sempre, so que marcado com o
 * token do prospecto — e por ele que o cadastro recebido volta a cair na ficha
 * de quem vinha conversando.
 */
class ProspectInviteMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Prospect $prospect,
        public string $link,
        public ?string $mensagem = null,
        public ?string $assinatura = null,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = SiteSetting::getValue('site_name', 'Associação Tech PG');

        return new Envelope(subject: "Convite para fazer parte da {$siteName}");
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.prospect-invite',
            with: [
                'nome' => $this->prospect->contact_name ?: $this->prospect->name,
                'siteName' => SiteSetting::getValue('site_name', 'Associação Tech PG'),
            ],
        );
    }
}
