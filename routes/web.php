<?php

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\CmsDashboardController;
use App\Http\Controllers\Admin\CmsModuleController;
use App\Http\Controllers\Admin\CmsPageController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\ColumnController as AdminColumnController;
use App\Http\Controllers\Admin\JobOpeningController as AdminJobController;
use App\Http\Controllers\Admin\MeetingController;
use App\Http\Controllers\Admin\MeetingMinuteController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\SpecialtyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Company\AccountController as CompanyAccountController;
use App\Http\Controllers\Company\DashboardController as CompanyDashboardController;
use App\Http\Controllers\Company\JobApplicationController as CompanyJobApplicationController;
use App\Http\Controllers\Company\JobOpeningController as CompanyJobController;
use App\Http\Controllers\Company\MeetingController as CompanyMeetingController;
use App\Http\Controllers\Company\MeetingMinuteController as CompanyMeetingMinuteController;
use App\Http\Controllers\Company\MemberController as CompanyMemberController;
use App\Http\Controllers\Company\ProfileController as CompanyProfileController;
use App\Http\Controllers\EditorImageController;
use App\Http\Controllers\MemberPanel\AccountController as MemberAccountController;
use App\Http\Controllers\Panel\ColumnController;
use App\Http\Controllers\Panel\ColumnistPhotoController;
use App\Http\Controllers\Panel\ColumnModerationController;
use App\Http\Controllers\MemberPanel\ProfileController as MemberProfileController;
use App\Http\Controllers\Portal\ColumnPageController;
use App\Http\Controllers\Portal\CompanyDirectoryController;
use App\Http\Controllers\Portal\EventPageController;
use App\Http\Controllers\Portal\HomeController;
use App\Http\Controllers\Portal\JobPageController;
use App\Http\Controllers\Portal\MeetingInvitationController;
use App\Http\Controllers\Portal\MemberDirectoryController;
use App\Http\Controllers\Portal\PageController;
use App\Http\Controllers\Portal\PostPageController;
use App\Http\Controllers\Portal\SitemapController;
use App\Http\Controllers\PublicCompanyRegistrationController;
use App\Http\Controllers\PublicMemberRegistrationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Registro em dois prefixos
|--------------------------------------------------------------------------
|
| O portal responde na raiz e tambem sob /atpg. Cada bloco de rotas e
| declarado uma unica vez e registrado nos dois prefixos: apenas o primeiro
| registro recebe nome, porque o AppServiceProvider ja forca a raiz /atpg na
| geracao de URLs quando a requisicao chega por la.
|
*/

$portalRoutes = function (bool $named = true): void {
    $route = function (string $method, string $uri, $action, ?string $name = null) use ($named) {
        $route = Route::{$method}($uri, $action);

        if ($named && $name) {
            $route->name($name);
        }

        return $route;
    };

    $route('get', '/', HomeController::class, 'home');

    $route('get', '/membros', [MemberDirectoryController::class, 'index'], 'members.index');
    $route('get', '/membros/{member:slug}', [MemberDirectoryController::class, 'show'], 'members.show');

    $route('get', '/empresas', [CompanyDirectoryController::class, 'index'], 'companies.index');
    $route('get', '/empresas/{company:slug}', [CompanyDirectoryController::class, 'show'], 'companies.show');

    $route('get', '/vagas', [JobPageController::class, 'index'], 'vagas.index');
    $route('get', '/vagas/{job:slug}', [JobPageController::class, 'show'], 'vagas.show');
    $route('post', '/vagas/{job:slug}/candidatura', [JobPageController::class, 'apply'], 'vagas.candidatura')
        ->middleware(['throttle:5,60', 'public-form']);

    $route('get', '/eventos', [EventPageController::class, 'index'], 'events');
    $route('get', '/eventos/{event:slug}', [EventPageController::class, 'show'], 'events.show');

    $route('get', '/colunas', [ColumnPageController::class, 'index'], 'colunas.index');
    $route('get', '/colunas/{slug}', [ColumnPageController::class, 'show'], 'colunas.show');

    $route('get', '/noticias', [PostPageController::class, 'index'], 'posts.index');
    $route('get', '/noticias/{post:slug}', [PostPageController::class, 'show'], 'posts.show');

    $route('get', '/sobre', [PageController::class, 'about'], 'about');
    $route('get', '/associe-se', [PageController::class, 'join'], 'join');
    $route('get', '/projetos', [PageController::class, 'projects'], 'projects');
    $route('get', '/beneficios', [PageController::class, 'benefits'], 'benefits');
    $route('get', '/governanca', [PageController::class, 'governance'], 'governance');
    $route('get', '/lgpd', [PageController::class, 'lgpd'], 'lgpd');
    $route('get', '/privacidade', [PageController::class, 'privacy'], 'privacy');
    $route('get', '/cookies', [PageController::class, 'cookies'], 'cookies');
    $route('get', '/pagina/{page:slug}', [PageController::class, 'show'], 'pages.show');

    $route('get', '/sitemap.xml', SitemapController::class, 'sitemap');

    // Convocacao aberta por link (enviada no WhatsApp). O token e o segredo.
    $route('get', '/reuniao/{token}', [MeetingInvitationController::class, 'show'], 'reuniao.convocacao');
    $route('post', '/reuniao/{token}/confirmar', [MeetingInvitationController::class, 'confirm'], 'reuniao.confirmar')
        ->middleware('throttle:20,1');
    $route('get', '/reuniao/{token}/agenda.ics', [MeetingInvitationController::class, 'calendar'], 'reuniao.calendario');

    // Endereco neutro do acesso. E a MESMA tela de /admin/login: existe um so
    // login, e o destino depois depende do papel (associacao ou empresa).
    // Sob /admin a URL sugeria "so administradores" para quem e de empresa.
    $route('get', '/entrar', [AuthenticatedSessionController::class, 'create'], 'entrar')
        ->middleware('guest');
    $route('post', '/entrar', [AuthenticatedSessionController::class, 'store'], 'entrar.store')
        ->middleware(['guest', 'throttle:10,1']);

    $route('get', '/cadastro-membro', [PublicMemberRegistrationController::class, 'create'], 'members.create');
    $route('post', '/cadastro-membro', [PublicMemberRegistrationController::class, 'store'], 'members.store')
        ->middleware(['throttle:5,60', 'public-form']);
    $route('get', '/cadastro-empresa', [PublicCompanyRegistrationController::class, 'create'], 'companies.register.create');
    $route('post', '/cadastro-empresa', [PublicCompanyRegistrationController::class, 'store'], 'companies.register.store')
        ->middleware(['throttle:5,60', 'public-form']);
};

$adminRoutes = function (bool $named = true): void {
    // Nomeia a rota apenas no primeiro registro; no segundo gera um nome
    // aleatorio para nao colidir com o primeiro.
    $as = function ($route, string $name) use ($named) {
        return $route->name($named ? $name : Str::random(16));
    };

    $resource = function (string $uri, string $controller, string $parameter, string $name) use ($named) {
        return Route::resource($uri, $controller)
            ->parameters([$uri => $parameter])
            ->except(['show'])
            ->names($named ? $name : Str::random(16));
    };

    Route::middleware('guest')->group(function () use ($as) {
        $as(Route::get('login', [AuthenticatedSessionController::class, 'create']), 'admin.login');
        $as(Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1'), 'admin.login.store');
    });

    Route::middleware(['auth', 'role:admin'])->group(function () use ($as, $resource, $named) {
        $as(Route::post('logout', [AuthenticatedSessionController::class, 'destroy']), 'admin.logout');
        $as(Route::get('/', DashboardController::class), 'admin.dashboard');

        $as(Route::get('perfil', [ProfileController::class, 'edit']), 'admin.profile.edit');
        $as(Route::put('perfil', [ProfileController::class, 'update']), 'admin.profile.update');
        $as(Route::put('perfil/senha', [ProfileController::class, 'updatePassword']), 'admin.profile.password');

        // ----- Empresas: rotas especificas antes do resource -----
        Route::middleware('module:companies')->group(function () use ($as, $resource) {
            $as(Route::get('empresas-pendentes', [CompanyController::class, 'pending']), 'admin.companies.pending');
            $as(Route::get('empresas/lixeira', [CompanyController::class, 'trash']), 'admin.companies.trash');
            $as(Route::patch('empresas/{company}/aprovar', [CompanyController::class, 'approve']), 'admin.companies.approve');
            $as(Route::patch('empresas/{company}/rejeitar', [CompanyController::class, 'reject']), 'admin.companies.reject');
            $as(Route::patch('empresas/{company}/restaurar', [CompanyController::class, 'restore']), 'admin.companies.restore');
            $as(Route::delete('empresas/{company}/excluir', [CompanyController::class, 'forceDelete']), 'admin.companies.force-delete');
            $as(Route::get('empresas/{company}/analisar', [CompanyController::class, 'show']), 'admin.companies.show');
            $as(Route::post('empresas/{company}/convite', [CompanyController::class, 'invite'])
                ->middleware('throttle:20,1'), 'admin.companies.invite');

            $resource('empresas', CompanyController::class, 'company', 'admin.companies');
        });

        // ----- Membros -----
        Route::middleware('module:members')->group(function () use ($as, $resource) {
            $as(Route::get('membros-pendentes', [MemberController::class, 'pending']), 'admin.members.pending');
            $as(Route::get('membros/lixeira', [MemberController::class, 'trash']), 'admin.members.trash');
            $as(Route::patch('membros/{member}/aprovar', [MemberController::class, 'approve']), 'admin.members.approve');
            $as(Route::patch('membros/{member}/rejeitar', [MemberController::class, 'reject']), 'admin.members.reject');
            $as(Route::patch('membros/{member}/restaurar', [MemberController::class, 'restore']), 'admin.members.restore');
            $as(Route::delete('membros/{member}/excluir', [MemberController::class, 'forceDelete']), 'admin.members.force-delete');
            $as(Route::get('membros/{member}/analisar', [MemberController::class, 'show']), 'admin.members.show');

            $resource('membros', MemberController::class, 'member', 'admin.members');
        });

        // ----- Reunioes -----
        Route::middleware('module:meetings')->group(function () use ($as, $resource) {
            $as(Route::post('reunioes/{reuniao}/lista', [MeetingController::class, 'refreshAttendanceList']), 'admin.reunioes.lista');
            $as(Route::post('reunioes/{reuniao}/presencas', [MeetingController::class, 'saveAttendance']), 'admin.reunioes.presencas');
            $as(Route::post('reunioes/{reuniao}/pauta/{item}/resultado', [MeetingController::class, 'saveOutcome']), 'admin.reunioes.resultado');
            $as(Route::post('reunioes/{reuniao}/encaminhamentos', [MeetingController::class, 'storeActionItem']), 'admin.reunioes.encaminhamentos.store');
            $as(Route::patch('encaminhamentos/{encaminhamento}', [MeetingController::class, 'updateActionItem']), 'admin.encaminhamentos.update');
            $as(Route::delete('encaminhamentos/{encaminhamento}', [MeetingController::class, 'destroyActionItem']), 'admin.encaminhamentos.destroy');
            $as(Route::post('reunioes/{reuniao}/gerar-ata', [MeetingController::class, 'generateMinute']), 'admin.reunioes.gerar-ata');
            // whereNumber: sem isso este curinga captura "reunioes/create", que e
            // registrado depois pelo resource, e tenta buscar a reuniao de id "create".
            $as(Route::get('reunioes/{reuniao}', [MeetingController::class, 'show'])
                ->whereNumber('reuniao'), 'admin.reunioes.show');
            $resource('reunioes', MeetingController::class, 'reuniao', 'admin.reunioes');
        });

        // ----- Atas de reuniao -----
        Route::middleware('module:minutes')->group(function () use ($as, $resource) {
            $as(Route::get('atas/{ata}/baixar', [MeetingMinuteController::class, 'download']), 'admin.atas.download');
            // whereNumber para nao capturar "atas/create", registrado pelo resource.
            $as(Route::get('atas/{ata}', [MeetingMinuteController::class, 'show'])
                ->whereNumber('ata'), 'admin.atas.show');
            $resource('atas', MeetingMinuteController::class, 'ata', 'admin.atas');
        });

        // ----- Vagas -----
        Route::middleware('module:jobs')->group(function () use ($as) {
            $as(Route::get('vagas-pendentes', [AdminJobController::class, 'pending']), 'admin.jobs.pending');
            $as(Route::get('vagas', [AdminJobController::class, 'index']), 'admin.jobs.index');
            $as(Route::get('vagas/{job}', [AdminJobController::class, 'show'])
                ->whereNumber('job'), 'admin.jobs.show');
            $as(Route::patch('vagas/{job}/aprovar', [AdminJobController::class, 'approve']), 'admin.jobs.approve');
            $as(Route::patch('vagas/{job}/rejeitar', [AdminJobController::class, 'reject']), 'admin.jobs.reject');
            $as(Route::patch('vagas/{job}/despublicar', [AdminJobController::class, 'unpublish']), 'admin.jobs.unpublish');
            $as(Route::patch('vagas/{job}/publicar', [AdminJobController::class, 'publish']), 'admin.jobs.publish');
            $as(Route::delete('vagas/{job}', [AdminJobController::class, 'destroy']), 'admin.jobs.destroy');
        });

        // ----- Colunas -----
        Route::middleware('module:columns')->group(function () use ($as) {
            $as(Route::get('colunas-pendentes', [AdminColumnController::class, 'pending']), 'admin.columns.pending');
            $as(Route::get('colunistas', [AdminColumnController::class, 'columnists']), 'admin.columns.columnists');
            $as(Route::get('colunas', [AdminColumnController::class, 'index']), 'admin.columns.index');
            $as(Route::get('colunas/{column}', [AdminColumnController::class, 'show'])
                ->whereNumber('column'), 'admin.columns.show');
            $as(Route::patch('colunas/{column}/aprovar', [AdminColumnController::class, 'approve']), 'admin.columns.approve');
            $as(Route::patch('colunas/{column}/rejeitar', [AdminColumnController::class, 'reject']), 'admin.columns.reject');
            $as(Route::patch('colunas/{column}/despublicar', [AdminColumnController::class, 'unpublish']), 'admin.columns.unpublish');
            $as(Route::patch('colunas/{column}/publicar', [AdminColumnController::class, 'publish']), 'admin.columns.publish');
        });

        Route::middleware('module:specialties')->group(function () use ($resource) {
            $resource('especialidades', SpecialtyController::class, 'specialty', 'admin.specialties');
        });

        Route::middleware('module:users')->group(function () use ($resource) {
            $resource('usuarios', UserController::class, 'user', 'admin.users');
        });

        Route::prefix('cms')->middleware('module:cms')->group(function () use ($as, $resource, $named) {
            $as(Route::get('/', CmsDashboardController::class), 'admin.cms.dashboard');
            $as(Route::get('configuracoes', [SiteSettingController::class, 'edit']), 'admin.cms.settings.edit');
            $as(Route::put('configuracoes', [SiteSettingController::class, 'update']), 'admin.cms.settings.update');

            // Antes do resource: "noticias/preview" bateria em noticias/{post}.
            // throttle porque cada chamada faz o servidor buscar um site externo.
            $as(Route::post('noticias/preview', [PostController::class, 'preview'])
                ->middleware('throttle:20,1'), 'admin.cms.posts.preview');

            $resource('noticias', PostController::class, 'post', 'admin.cms.posts');
            $resource('eventos', EventController::class, 'event', 'admin.cms.events');
            $resource('paginas', CmsPageController::class, 'page', 'admin.cms.pages');

            Route::resource('modulos/{module}/itens', CmsModuleController::class)
                ->parameters(['itens' => 'item'])
                ->except(['show'])
                ->names($named ? 'admin.cms.modules' : Str::random(16));
        });
    });
};

/*
|--------------------------------------------------------------------------
| Painel da empresa associada
|--------------------------------------------------------------------------
|
| Mesmo login do painel administrativo; o middleware de papel separa os dois.
| Todo acesso e escopado a empresa do usuario.
|
*/

$companyRoutes = function (bool $named = true): void {
    $as = fn ($route, string $name) => $route->name($named ? $name : Str::random(16));

    Route::middleware(['auth', 'role:company'])->group(function () use ($as) {
        $as(Route::get('/', CompanyDashboardController::class), 'empresa.dashboard');

        $as(Route::get('perfil', [CompanyProfileController::class, 'edit']), 'empresa.perfil.edit');
        $as(Route::put('perfil', [CompanyProfileController::class, 'update']), 'empresa.perfil.update');

        $as(Route::get('conta', [CompanyAccountController::class, 'edit']), 'empresa.conta.edit');
        $as(Route::put('conta', [CompanyAccountController::class, 'update']), 'empresa.conta.update');
        $as(Route::put('conta/senha', [CompanyAccountController::class, 'updatePassword']), 'empresa.conta.senha');

        $as(Route::get('reunioes', [CompanyMeetingController::class, 'index']), 'empresa.reunioes.index');
        $as(Route::post('reunioes/{reuniao}/confirmar', [CompanyMeetingController::class, 'confirm']), 'empresa.reunioes.confirmar');

        $as(Route::get('atas', [CompanyMeetingMinuteController::class, 'index']), 'empresa.atas.index');
        $as(Route::get('atas/{ata}/baixar', [CompanyMeetingMinuteController::class, 'download']), 'empresa.atas.download');
        $as(Route::get('atas/{ata}', [CompanyMeetingMinuteController::class, 'show']), 'empresa.atas.show');

        // ----- Vagas da empresa -----
        $as(Route::get('vagas', [CompanyJobController::class, 'index']), 'empresa.vagas.index');
        $as(Route::get('vagas/nova', [CompanyJobController::class, 'create']), 'empresa.vagas.create');
        $as(Route::post('vagas', [CompanyJobController::class, 'store']), 'empresa.vagas.store');
        $as(Route::get('vagas/{job}/editar', [CompanyJobController::class, 'edit']), 'empresa.vagas.edit');
        $as(Route::put('vagas/{job}', [CompanyJobController::class, 'update']), 'empresa.vagas.update');
        $as(Route::patch('vagas/{job}/encerrar', [CompanyJobController::class, 'close']), 'empresa.vagas.encerrar');
        $as(Route::delete('vagas/{job}', [CompanyJobController::class, 'destroy']), 'empresa.vagas.destroy');

        // Candidaturas: dado pessoal de terceiro, so a empresa dona da vaga ve.
        $as(Route::get('vagas/{job}/candidaturas', [CompanyJobApplicationController::class, 'index']), 'empresa.vagas.candidaturas');
        $as(Route::get('vagas/{job}/candidaturas/{application}', [CompanyJobApplicationController::class, 'show']), 'empresa.vagas.candidatura');
        $as(Route::get('vagas/{job}/candidaturas/{application}/curriculo', [CompanyJobApplicationController::class, 'download']), 'empresa.vagas.curriculo');
        $as(Route::delete('vagas/{job}/candidaturas/{application}', [CompanyJobApplicationController::class, 'destroy']), 'empresa.vagas.candidatura.destroy');

        // ----- Moderacao delegada das colunas do portal -----
        // Aberto so para a empresa marcada em `moderates_columns`; a checagem
        // fica no controller, que responde 403 para as demais.
        $as(Route::get('moderacao/colunas', [ColumnModerationController::class, 'index']), 'empresa.moderacao.index');
        $as(Route::get('moderacao/colunas/{column}', [ColumnModerationController::class, 'show'])
            ->whereNumber('column'), 'empresa.moderacao.show');
        $as(Route::patch('moderacao/colunas/{column}/aprovar', [ColumnModerationController::class, 'approve']), 'empresa.moderacao.aprovar');
        $as(Route::patch('moderacao/colunas/{column}/rejeitar', [ColumnModerationController::class, 'reject']), 'empresa.moderacao.rejeitar');
        $as(Route::patch('moderacao/colunas/{column}/despublicar', [ColumnModerationController::class, 'unpublish']), 'empresa.moderacao.despublicar');

        // ----- Colunas da empresa colunista -----
        $as(Route::get('colunas', [ColumnController::class, 'index']), 'empresa.colunas.index');
        $as(Route::get('colunas/nova', [ColumnController::class, 'create']), 'empresa.colunas.create');
        $as(Route::post('colunas', [ColumnController::class, 'store']), 'empresa.colunas.store');
        $as(Route::get('colunas/{column}/editar', [ColumnController::class, 'edit']), 'empresa.colunas.edit');
        $as(Route::put('colunas/{column}', [ColumnController::class, 'update']), 'empresa.colunas.update');
        $as(Route::delete('colunas/{column}', [ColumnController::class, 'destroy']), 'empresa.colunas.destroy');

        // Foto propria de quem assina. Caminho fora de `colunas/` para nao
        // cair na rota `colunas/{column}`, que aceitaria "foto" como id.
        $as(Route::put('perfil-colunista/foto', [ColumnistPhotoController::class, 'update']), 'empresa.colunas.foto.update');
        $as(Route::delete('perfil-colunista/foto', [ColumnistPhotoController::class, 'destroy']), 'empresa.colunas.foto.destroy');

        $as(Route::get('colaboradores', [CompanyMemberController::class, 'index']), 'empresa.membros.index');
        $as(Route::get('colaboradores/novo', [CompanyMemberController::class, 'create']), 'empresa.membros.create');
        $as(Route::post('colaboradores', [CompanyMemberController::class, 'store']), 'empresa.membros.store');
        $as(Route::get('colaboradores/{member}/editar', [CompanyMemberController::class, 'edit']), 'empresa.membros.edit');
        $as(Route::put('colaboradores/{member}', [CompanyMemberController::class, 'update']), 'empresa.membros.update');
        $as(Route::delete('colaboradores/{member}', [CompanyMemberController::class, 'destroy']), 'empresa.membros.destroy');
    });
};

/*
|--------------------------------------------------------------------------
| Recuperacao de senha (comum aos dois paineis)
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Painel do membro
|--------------------------------------------------------------------------
|
| Mesmo login dos demais paineis. O membro so alcanca o proprio cadastro: nao
| ha id na URL em lugar nenhum daqui.
|
*/

$memberRoutes = function (bool $named = true): void {
    $as = fn ($route, string $name) => $route->name($named ? $name : Str::random(16));

    Route::middleware(['auth', 'role:member'])->group(function () use ($as) {
        $as(Route::get('/', [MemberProfileController::class, 'edit']), 'membro.perfil.edit');
        $as(Route::put('/', [MemberProfileController::class, 'update']), 'membro.perfil.update');

        // ----- Colunas do membro colunista -----
        $as(Route::get('colunas', [ColumnController::class, 'index']), 'membro.colunas.index');
        $as(Route::get('colunas/nova', [ColumnController::class, 'create']), 'membro.colunas.create');
        $as(Route::post('colunas', [ColumnController::class, 'store']), 'membro.colunas.store');
        $as(Route::get('colunas/{column}/editar', [ColumnController::class, 'edit']), 'membro.colunas.edit');
        $as(Route::put('colunas/{column}', [ColumnController::class, 'update']), 'membro.colunas.update');
        $as(Route::delete('colunas/{column}', [ColumnController::class, 'destroy']), 'membro.colunas.destroy');
        $as(Route::put('perfil-colunista/foto', [ColumnistPhotoController::class, 'update']), 'membro.colunas.foto.update');
        $as(Route::delete('perfil-colunista/foto', [ColumnistPhotoController::class, 'destroy']), 'membro.colunas.foto.destroy');

        $as(Route::get('conta', [MemberAccountController::class, 'edit']), 'membro.conta.edit');
        $as(Route::put('conta', [MemberAccountController::class, 'update']), 'membro.conta.update');
        $as(Route::put('conta/senha', [MemberAccountController::class, 'updatePassword']), 'membro.conta.senha');
    });
};

$passwordRoutes = function (bool $named = true): void {
    $as = fn ($route, string $name) => $route->name($named ? $name : Str::random(16));

    Route::middleware('guest')->group(function () use ($as) {
        $as(Route::get('esqueci-senha', [PasswordResetLinkController::class, 'create']), 'password.request');
        $as(Route::post('esqueci-senha', [PasswordResetLinkController::class, 'store'])
            ->middleware('throttle:6,1'), 'password.email');
        $as(Route::get('redefinir-senha/{token}', [NewPasswordController::class, 'create']), 'password.reset');
        $as(Route::post('redefinir-senha', [NewPasswordController::class, 'store'])
            ->middleware('throttle:6,1'), 'password.update');
    });
};

/*
 * Envio de imagem de dentro do editor de textos.
 *
 * Fica fora dos grupos de papel porque os tres paineis usam o mesmo editor:
 * diretoria escreve noticia, empresa escreve vaga e coluna, membro escreve
 * coluna. Só sessao valida e exigida, com limite de chamadas para o campo de
 * texto nao virar hospedagem de arquivos.
 */
$editorRoutes = function (bool $named = true): void {
    $rota = Route::post('painel/editor/imagem', EditorImageController::class)
        ->middleware(['auth', 'throttle:60,1']);

    $rota->name($named ? 'editor.imagem' : Str::random(16));
};

$portalRoutes();
Route::prefix('atpg')->group(fn () => $portalRoutes(false));

$editorRoutes();
Route::prefix('atpg')->group(fn () => $editorRoutes(false));

$passwordRoutes();
Route::prefix('atpg')->group(fn () => $passwordRoutes(false));

Route::prefix('admin')->group(fn () => $adminRoutes());
Route::prefix('atpg/admin')->group(fn () => $adminRoutes(false));

Route::prefix('empresa')->group(fn () => $companyRoutes());
Route::prefix('atpg/empresa')->group(fn () => $companyRoutes(false));

Route::prefix('membro')->group(fn () => $memberRoutes());
Route::prefix('atpg/membro')->group(fn () => $memberRoutes(false));

Route::get('/login', fn () => redirect()->route('entrar'))->name('login');
Route::get('/atpg/login', fn () => redirect()->route('entrar'));
