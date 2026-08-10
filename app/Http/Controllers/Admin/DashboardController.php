<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Company;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use App\Models\Specialty;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('portal.admin', [
            'stats' => $this->stats(),
            'pendingCompanies' => Company::query()
                ->withCount('members')
                ->pending()
                ->latest()
                ->limit(5)
                ->get(),
            'pendingMembers' => Member::query()
                ->with('company:id,name')
                ->pending()
                ->latest()
                ->limit(5)
                ->get(),
            'recentMembers' => Member::query()
                ->with(['company:id,name', 'specialties:id,name'])
                ->latest()
                ->limit(6)
                ->get(),
            'upcomingEvents' => Event::query()
                ->where('event_date', '>=', now()->startOfDay())
                ->orderBy('event_date')
                ->limit(4)
                ->get(),
        ]);
    }

    /**
     * Contagens agregadas no banco. Antes o painel carregava colecoes
     * inteiras so para chamar ->count() em cima.
     *
     * @return array<string, int>
     */
    private function stats(): array
    {
        return [
            'members' => Member::count(),
            'membersPublished' => Member::visible()->count(),
            'membersPending' => Member::pending()->count(),
            'companies' => Company::count(),
            'companiesPublished' => Company::visible()->count(),
            'companiesPending' => Company::pending()->count(),
            'specialties' => Specialty::count(),
            'posts' => Post::count(),
            'postsPublished' => Post::published()->count(),
            'events' => Event::count(),
            'eventsUpcoming' => Event::published()->where('event_date', '>=', now()->startOfDay())->count(),
            'pages' => CmsPage::count(),
        ];
    }
}
