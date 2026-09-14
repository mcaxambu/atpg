<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Columnist;
use App\Models\Post;
use App\Support\Portal\PortalData;
use Illuminate\View\View;

class ColumnPageController extends Controller
{
    /** Vitrine dos colunistas, com as colunas mais recentes de cada um. */
    public function index(PortalData $portalData): View
    {
        $colunistas = Columnist::query()
            ->publiclyVisible()
            ->with('member:id,name,slug,photo_path,avatar_initials,summary', 'company:id,name,slug,logo_path,initials,description')
            ->withCount(['posts' => fn ($q) => $q->published()])
            ->get()
            // Quem ainda nao publicou nada nao ocupa espaco na vitrine.
            ->filter(fn (Columnist $c) => $c->posts_count > 0)
            ->sortBy(fn (Columnist $c) => mb_strtolower($c->display_name))
            ->values();

        return view('portal.columns.index', $portalData->merge([
            'columnists' => $colunistas,
            'latestColumns' => Post::query()
                ->columns()
                ->visibleToPublic()
                ->with('columnist.member', 'columnist.company')
                ->latest('published_at')
                ->limit(6)
                ->get(),
        ]));
    }

    /** Pagina do colunista: apresentacao e os textos dele. */
    public function show(string $slug, PortalData $portalData): View
    {
        $colunista = Columnist::query()
            ->where('slug', $slug)
            ->with('member', 'company')
            ->firstOrFail();

        abort_unless($colunista->isPubliclyVisible(), 404);

        return view('portal.columns.show', $portalData->merge([
            'columnist' => $colunista,
            'columns' => $colunista->posts()
                ->published()
                ->latest('published_at')
                ->paginate(9),
        ]));
    }
}
