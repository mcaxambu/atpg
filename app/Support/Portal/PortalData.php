<?php

namespace App\Support\Portal;

use App\Models\CmsItem;
use App\Models\CmsPage;
use App\Models\Company;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use App\Models\SiteSetting;
use App\Models\Specialty;
use Illuminate\Support\Facades\Cache;

/**
 * Payload compartilhado por todas as paginas do portal (menu, rodape, home).
 *
 * Antes carregava TODAS as empresas e TODOS os membros com relacoes em toda
 * requisicao. Agora sao apenas contagens e recortes com LIMIT.
 *
 * Só as contagens vão para o cache: elas são as consultas caras e são
 * escalares. Modelos Eloquent NAO podem ser cacheados no driver de banco —
 * a serializacao de objetos contem bytes nulos que a coluna utf8mb4 corrompe,
 * e a leitura volta como __PHP_Incomplete_Class.
 */
class PortalData
{
    public const CACHE_KEY = 'portal.counts';

    private const CACHE_TTL = 3600;

    public function shared(): array
    {
        return array_merge($this->counts(), $this->content());
    }

    public function merge(array $data = []): array
    {
        return array_merge($this->shared(), $data);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => [
            'memberCount' => Member::publiclyVisible()->count(),
            'companyCount' => Company::visible()->count(),
            'specialtyCount' => Specialty::count(),
        ]);
    }

    private function content(): array
    {
        return [
            'featuredMembers' => Member::query()
                ->with(['company:id,name,slug', 'specialties:id,name,slug'])
                ->publiclyVisible()
                ->orderByDesc('is_featured')
                ->orderBy('name')
                ->limit(6)
                ->get(),

            'featuredCompanies' => Company::query()
                ->withCount(['members' => fn ($query) => $query->visible()])
                ->visible()
                ->orderByDesc('members_count')
                ->orderBy('name')
                ->limit(4)
                ->get(),

            'specialties' => Specialty::query()->orderBy('name')->get(),

            'latestEvents' => Event::query()
                ->published()
                ->where('event_date', '>=', now()->subDay())
                ->orderBy('event_date')
                ->orderBy('starts_at')
                ->limit(3)
                ->get(),

            'latestPosts' => Post::query()
                ->published()
                ->latest('published_at')
                ->limit(3)
                ->get(),

            'menuPages' => CmsPage::query()
                ->published()
                ->where('show_in_menu', true)
                ->orderBy('position')
                ->orderBy('title')
                ->get(),

            'cmsBanners' => $this->cmsItems('banners', 3),
            'cmsHighlights' => $this->cmsItems('destaques', 6),
            'cmsProjects' => $this->cmsItems('projetos', 6),
            'cmsPartners' => $this->cmsItems('parceiros', 8),
            'cmsTestimonials' => $this->cmsItems('depoimentos', 3),

            'siteSettings' => SiteSetting::allSettings(),
        ];
    }

    private function cmsItems(string $module, int $limit)
    {
        return CmsItem::query()
            ->module($module)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderBy('position')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
