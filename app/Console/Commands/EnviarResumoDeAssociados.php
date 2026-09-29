<?php

namespace App\Console\Commands;

use App\Enums\AdminModule;
use App\Enums\UserRole;
use App\Mail\WeeklyFunnelDigestMail;
use App\Models\Company;
use App\Models\Member;
use App\Models\Prospect;
use App\Models\User;
use App\Support\OnboardingProgress;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Resumo semanal do funil e das entradas, por e-mail, para a diretoria.
 *
 * Vai para quem decide, e nao para o associado: cobranca automatica a quem
 * acabou de entrar vira spam. O associado so recebe lembrete quando alguem
 * aperta o botao na tela de acompanhamento.
 *
 * Se nao houver nada a dizer, o comando NAO manda e-mail. Resumo que chega
 * toda semana dizendo "nada pendente" ensina a diretoria a ignorar o resumo.
 */
class EnviarResumoDeAssociados extends Command
{
    protected $signature = 'atpg:resumo-associados
                            {--forcar : Envia mesmo sem nada pendente}
                            {--para= : E-mail de teste, em vez da diretoria}';

    protected $description = 'Manda para a diretoria o resumo do funil e de quem entrou faltando completar o perfil';

    public function handle(): int
    {
        $atrasados = Prospect::atrasados()->with('owner')->orderBy('next_action_at')->get();
        $parados = Prospect::emAndamento()->get()->filter(fn (Prospect $p) => $p->parado() && ! $p->atrasado())->values();

        $incompletos = collect()
            ->merge(Company::approved()->where('created_at', '>=', now()->subDays(180))->get())
            ->merge(Member::approved()->where('created_at', '>=', now()->subDays(180))->get())
            ->map(fn (Company|Member $a) => new OnboardingProgress($a))
            ->reject(fn (OnboardingProgress $p) => $p->completo())
            ->sortByDesc(fn (OnboardingProgress $p) => $p->diasDesdeAprovacao())
            ->values();

        $temAlgoADizer = $atrasados->isNotEmpty() || $parados->isNotEmpty() || $incompletos->isNotEmpty();

        if (! $temAlgoADizer && ! $this->option('forcar')) {
            $this->info('Nada pendente; nenhum e-mail enviado.');

            return self::SUCCESS;
        }

        $destinatarios = $this->destinatarios();

        if ($destinatarios === []) {
            $this->warn('Nenhum destinatário com acesso ao módulo de novos associados.');

            return self::SUCCESS;
        }

        foreach ($destinatarios as $email) {
            Mail::to($email)->send(new WeeklyFunnelDigestMail($atrasados, $parados, $incompletos));
        }

        $this->info(sprintf(
            'Resumo enviado para %d destinatário(s): %d atrasados, %d parados, %d perfis incompletos.',
            count($destinatarios),
            $atrasados->count(),
            $parados->count(),
            $incompletos->count()
        ));

        return self::SUCCESS;
    }

    /** @return array<int, string> */
    private function destinatarios(): array
    {
        if ($teste = $this->option('para')) {
            return [$teste];
        }

        return User::query()
            ->where('role', UserRole::Admin)
            ->whereNotNull('email')
            ->get()
            ->filter(fn (User $u) => $u->canAccessModule(AdminModule::Crm))
            ->pluck('email')
            ->unique()
            ->values()
            ->all();
    }
}
