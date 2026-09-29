<?php

namespace App\Providers;

use App\Models\CmsItem;
use App\Models\CmsPage;
use App\Models\Company;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use App\Models\Specialty;
use App\Observers\PortalContentObserver;
use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
// `Event` neste arquivo ja e o model de eventos do site; o facade entra com
// apelido para os dois conviverem sem confusao.
use Illuminate\Support\Facades\Event as EventFacade;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Modelos cujo conteudo aparece no site publico e, portanto, invalidam
     * o cache compartilhado do portal quando mudam.
     */
    private const PORTAL_CONTENT_MODELS = [
        Company::class,
        Member::class,
        Post::class,
        Event::class,
        CmsPage::class,
        CmsItem::class,
        Specialty::class,
    ];

    public function register(): void
    {
        /*
         * Na producao o DirectAdmin serve o site a partir de public_html, que
         * fica fora da pasta da aplicacao. Sem avisar o Laravel, public_path()
         * aponta para uma copia que ninguem le — e o ?v= do portal.css, que sai
         * do filemtime desse arquivo, congela num valor antigo. O navegador
         * entao guarda o CSS velho para sempre, e cada publicacao parece nao
         * ter efeito.
         *
         * Em desenvolvimento a variavel fica vazia e vale o padrao.
         */
        if ($publico = env('APP_PUBLIC_PATH')) {
            $this->app->usePublicPath($publico);
        }
    }

    public function boot(): void
    {
        foreach (self::PORTAL_CONTENT_MODELS as $model) {
            $model::observe(PortalContentObserver::class);
        }

        // O Laravel 11+ nao traz mais um limitador "api" pronto; sem ele o
        // middleware throttle:api das rotas da API estoura em tempo de request.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        /*
         * Data do ultimo acesso.
         *
         * E o sinal que responde "a empresa aprovada chegou a entrar no
         * painel?", no acompanhamento de novos associados. Sem ele so daria
         * para saber perguntando — o trabalho manual que o modulo existe para
         * evitar. `saveQuietly` porque isto nao e edicao de conteudo e nao deve
         * acordar observers nem mexer em `updated_at`.
         */
        EventFacade::listen(Login::class, function (Login $evento): void {
            $evento->user->forceFill(['last_login_at' => now()])->saveQuietly();
        });

        Paginator::defaultView('pagination::tailwind');
        Paginator::defaultSimpleView('pagination::simple-tailwind');

        // Fora de producao, atribuicao inesperada vira erro em vez de passar
        // despercebida ate o deploy.
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        Carbon::setLocale(config('app.locale'));
    }
}
