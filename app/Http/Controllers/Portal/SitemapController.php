<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use App\Models\Company;
use App\Models\Columnist;
use App\Models\Event;
use App\Models\JobOpening;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $urls = collect();

        foreach ([
            ['home', 'daily', '1.0'],
            ['companies.index', 'daily', '0.9'],
            ['members.index', 'daily', '0.9'],
            ['vagas.index', 'daily', '0.8'],
            ['colunas.index', 'weekly', '0.7'],
            ['events', 'weekly', '0.8'],
            ['posts.index', 'daily', '0.8'],
            ['join', 'monthly', '0.7'],
            ['about', 'monthly', '0.6'],
            ['benefits', 'monthly', '0.6'],
            ['projects', 'monthly', '0.6'],
            ['governance', 'yearly', '0.4'],
            ['companies.register.create', 'monthly', '0.7'],
            ['members.create', 'monthly', '0.7'],
            ['privacy', 'yearly', '0.3'],
            ['lgpd', 'yearly', '0.3'],
            ['cookies', 'yearly', '0.3'],
        ] as [$route, $frequency, $priority]) {
            $urls->push([route($route), null, $frequency, $priority]);
        }

        Company::visible()->get(['slug', 'updated_at'])->each(
            fn (Company $company) => $urls->push([route('companies.show', $company->slug), $company->updated_at, 'weekly', '0.7'])
        );

        Member::publiclyVisible()->get(['id', 'company_id', 'slug', 'updated_at'])->each(
            fn (Member $member) => $urls->push([route('members.show', $member->slug), $member->updated_at, 'weekly', '0.6'])
        );

        JobOpening::publiclyVisible()->get(['id', 'company_id', 'slug', 'updated_at'])->each(
            fn (JobOpening $job) => $urls->push([route('vagas.show', $job->slug), $job->updated_at, 'daily', '0.7'])
        );

        Columnist::publiclyVisible()->get(['id', 'member_id', 'company_id', 'slug', 'updated_at'])->each(
            fn (Columnist $columnist) => $urls->push([route('colunas.show', $columnist->slug), $columnist->updated_at, 'weekly', '0.6'])
        );

        Post::published()->get(['slug', 'updated_at'])->each(
            fn (Post $post) => $urls->push([route('posts.show', $post->slug), $post->updated_at, 'monthly', '0.6'])
        );

        Event::published()->get(['slug', 'updated_at'])->each(
            fn (Event $event) => $urls->push([route('events.show', $event->slug), $event->updated_at, 'weekly', '0.6'])
        );

        CmsPage::published()->get(['slug', 'updated_at'])->each(
            fn (CmsPage $page) => $urls->push([route('pages.show', $page->slug), $page->updated_at, 'monthly', '0.5'])
        );

        return response()
            ->view('portal.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
