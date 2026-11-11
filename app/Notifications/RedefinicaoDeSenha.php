<?php

namespace App\Notifications;

use App\Models\SiteSetting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * "Esqueci minha senha", em portugues.
 *
 * O Laravel manda esse e-mail em ingles ("Reset your password"), e era o unico
 * texto do portal que chegava assim ao associado — logo no momento em que ele
 * esta com dificuldade de entrar, que e quando menos ajuda estranhar a
 * mensagem. O convite de acesso ja era traduzido; este ficou para tras.
 */
class RedefinicaoDeSenha extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $siteName = SiteSetting::getValue('site_name', 'Associação Tech PG');

        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $expira = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject("Redefinição de senha - {$siteName}")
            ->greeting("Olá, {$notifiable->name}")
            ->line('Recebemos um pedido para redefinir a senha do seu acesso ao portal.')
            ->action('Criar uma nova senha', $url)
            ->line("Este link expira em {$expira} minutos.")
            ->line('Se não foi você que pediu, pode ignorar esta mensagem: sua senha continua a mesma.')
            ->salutation("Abraço,\n{$siteName}");
    }
}
