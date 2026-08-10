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
use Illuminate\Cache\RateLimiting\Limit;
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
        //
    }

    public function boot(): void
    {
        foreach (self::PORTAL_CONTENT_MODELS as $model) {
            $model::observe(PortalContentObserver::class);
        }

        // O Laravel 11+ nao traz mais um limitador "api" pronto; sem ele o
        // middleware throttle:api das rotas da API estoura em tempo de request.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->ip()));

        Paginator::defaultView('pagination::tailwind');
        Paginator::defaultSimpleView('pagination::simple-tailwind');

        // Fora de producao, atribuicao inesperada vira erro em vez de passar
        // despercebida ate o deploy.
        Model::preventSilentlyDiscardingAttributes(! app()->isProduction());

        Carbon::setLocale(config('app.locale'));
    }
}
