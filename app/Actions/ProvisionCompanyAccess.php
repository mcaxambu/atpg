<?php

namespace App\Actions;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Garante que a empresa tenha um acesso ao painel e envia o convite para o
 * responsavel definir a senha.
 *
 * Chamado automaticamente na aprovacao e manualmente pelo botao "Enviar
 * convite" na tela da empresa.
 */
class ProvisionCompanyAccess
{
    public const INVITED = 'invited';

    public const RESENT = 'resent';

    public const ALREADY_ACTIVE = 'already_active';

    public const NO_EMAIL = 'no_email';

    public const EMAIL_TAKEN = 'email_taken';

    public const SEND_FAILED = 'send_failed';

    /**
     * @return array{status: string, user: ?User}
     */
    public function __invoke(Company $company, bool $force = false): array
    {
        if (blank($company->email)) {
            Log::warning('Empresa sem e-mail: acesso ao painel nao pode ser criado.', [
                'company_id' => $company->getKey(),
            ]);

            return ['status' => self::NO_EMAIL, 'user' => null];
        }

        $existing = User::query()->where('email', $company->email)->first();

        // E-mail ja usado por outra conta (inclusive por um administrador):
        // nao sequestramos o usuario nem sobrescrevemos o papel dele.
        if ($existing && $existing->company_id !== $company->id) {
            Log::warning('E-mail da empresa ja pertence a outro usuario; acesso nao criado.', [
                'company_id' => $company->getKey(),
                'user_id' => $existing->getKey(),
            ]);

            return ['status' => self::EMAIL_TAKEN, 'user' => null];
        }

        $user = $existing;

        if (! $user) {
            $user = User::create([
                'name' => $company->contact_name ?: $company->name,
                'email' => $company->email,
                'role' => UserRole::Company,
                'company_id' => $company->id,
            ]);
        }

        // Reenviar para quem ja usa o painel invalidaria nada, mas e ruido —
        // e um convite inesperado e um bom isca de phishing. So com --force,
        // quando o administrador pede explicitamente.
        if ($user->hasActivatedAccess() && ! $force) {
            return ['status' => self::ALREADY_ACTIVE, 'user' => $user];
        }

        $wasInvited = $user->invited_at !== null;

        /*
         * O envio nao pode derrubar a operacao.
         *
         * Isto e chamado de dentro da aprovacao: quando o servidor de e-mail
         * esta fora do ar, a excecao do transporte subia ate virar erro 500 e
         * o administrador ficava sem saber que a empresa ja tinha sido
         * aprovada — porque tinha, a gravacao acontece antes.
         *
         * O acesso fica criado de qualquer forma; basta reenviar o convite
         * quando o e-mail voltar.
         */
        try {
            $user->sendCompanyInvitation();
        } catch (\Throwable $exception) {
            Log::error('Falha ao enviar o convite de acesso ao painel.', [
                'company_id' => $company->getKey(),
                'user_id' => $user->getKey(),
                'message' => $exception->getMessage(),
            ]);

            return ['status' => self::SEND_FAILED, 'user' => $user];
        }

        return [
            'status' => $wasInvited ? self::RESENT : self::INVITED,
            'user' => $user,
        ];
    }
}
