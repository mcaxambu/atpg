<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\Portal\PortalData;

class PostPageController extends Controller
{
    public function index(PortalData $portalData)
    {
        $posts = Post::query()
            ->published()
            ->latest('published_at')
            ->paginate(9);

        /*
         * A mais recente vira destaque, mas so na primeira pagina: "destaque"
         * na pagina 3 nao quer dizer nada, e o leitor que ja esta paginando
         * quer varrer titulos, nao um banner por pagina.
         */
        $featured = $posts->onFirstPage() ? $posts->getCollection()->first() : null;

        return view('portal.posts.index', $portalData->merge([
            'posts' => $posts,
            'featured' => $featured,
            'otherPosts' => $featured
                ? $posts->getCollection()->slice(1)
                : $posts->getCollection(),
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
