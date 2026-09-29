<?php

namespace App\Mail;

use App\Models\SiteSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

/**
 * Resumo semanal do funil para a diretoria.
 *
 * Conteudo em ordem de urgencia: quem tem prazo vencido, quem parou de andar
 * e quem entrou mas nao completou o perfil.
 */
class WeeklyFunnelDigestMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Collection $atrasados,
        public Collection $parados,
        public Collection $incompletos,
    ) {}

    public function envelope(): Envelope
    {
        $siteName = SiteSetting::getValue('site_name', 'Associação Tech PG');

        $pendencias = $this->atrasados->count() + $this->parados->count() + $this->incompletos->count();

        $assunto = $pendencias === 0
            ? "Novos associados: nada pendente - {$siteName}"
            : "Novos associados: {$pendencias} ".($pendencias === 1 ? 'pendência' : 'pendências')." - {$siteName}";

        return new Envelope(subject: $assunto);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.weekly-funnel-digest',
            with: [
                'funil' => route('admin.crm.index'),
                'acompanhamento' => route('admin.crm.onboarding'),
                'siteName' => SiteSetting::getValue('site_name', 'Associação Tech PG'),
            ],
        );
    }
}
