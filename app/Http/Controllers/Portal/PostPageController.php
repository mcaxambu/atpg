<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\Portal\PortalData;

class PostPageController extends Controller
{
    public function index(PortalData $portalData)
    {
        return view('portal.posts.index', $portalData->merge([
            'posts' => Post::query()
                ->published()
                ->latest('published_at')
                ->paginate(9),
        ]));
    }

    public function show(Post $post, PortalData $portalData)
    {
        abort_unless($post->is_published && $post->published_at && $post->published_at->lte(now()), 404);

        return view('portal.posts.show', $portalData->merge([
            'post' => $post,
            'relatedPosts' => Post::query()
                ->published()
                ->whereKeyNot($post->id)
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]));
    }
}
