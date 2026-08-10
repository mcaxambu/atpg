<?php

namespace App\Notifications;

use App\Models\SiteSetting;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Convite de acesso ao painel da empresa, enviado quando a associacao aprova
 * o cadastro. Reaproveita o token de redefinicao de senha do Laravel: o
 * responsavel define a senha e ja entra no painel.
 */
class CompanyAccessInvitation extends ResetPassword
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
            ->subject("Seu acesso ao painel - {$siteName}")
            ->greeting("Olá, {$notifiable->name}")
            ->line("A **{$notifiable->company?->name}** foi aprovada e já está publicada no {$siteName}.")
            ->line('Criamos um acesso para você gerenciar a página da empresa: atualizar dados, trocar a logo e cadastrar os colaboradores que aparecem no portal.')
            ->action('Definir minha senha', $url)
            ->line("Este link expira em {$expira} minutos. Se ele vencer, use a opção \"Esqueci minha senha\" na tela de acesso.")
            ->salutation("Abraço,\n{$siteName}");
    }
}
