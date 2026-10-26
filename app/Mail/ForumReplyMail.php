<?php

namespace App\Mail;

use App\Models\ForumReply;
use App\Models\ForumTopic;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso de resposta nova para quem participa do tópico.
 *
 * O trecho da resposta vai no corpo de propósito: a maior parte das vezes a
 * pessoa só quer saber o que foi dito, e um e-mail que obriga a abrir o painel
 * para descobrir isso vira e-mail ignorado.
 */
class ForumReplyMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public ForumTopic $topic,
        public ForumReply $reply,
        public User $destinatario,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nova resposta: '.$this->topic->title);
    }

    public function content(): Content
    {
        $ehEmpresa = $this->destinatario->isCompany();

        return new Content(
            markdown: 'emails.forum-reply',
            with: [
                'nome' => $this->destinatario->name,
                'autor' => $this->reply->author?->assinaturaNoForum() ?? 'Alguém',
                'titulo' => $this->topic->title,
                'trecho' => str($this->reply->rendered_body)->stripTags()->squish()->limit(400)->value(),
                'link' => route($ehEmpresa ? 'empresa.forum.show' : 'membro.forum.show', $this->topic->slug),
                'siteName' => SiteSetting::getValue('site_name', 'Associação Tech PG'),
            ],
        );
    }
}
