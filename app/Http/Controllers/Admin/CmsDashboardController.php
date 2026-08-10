<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CmsItem;
use App\Models\CmsPage;
use App\Models\Event;
use App\Models\Post;

class CmsDashboardController extends Controller
{
    public function __invoke()
    {
        return view('portal.admin.cms.dashboard', [
            'posts' => Post::query()->latest()->get(),
            'events' => Event::query()->latest('event_date')->latest()->get(),
            'pages' => CmsPage::query()->orderBy('position')->orderBy('title')->get(),
            'cmsItems' => CmsItem::query()->latest()->get(),
            'moduleCounts' => CmsItem::query()
                ->selectRaw('module, count(*) as total')
                ->groupBy('module')
                ->pluck('total', 'module'),
        ]);
    }
}
