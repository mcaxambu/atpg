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
            ->visibleToPublic()
            ->inFeedOrder()
            ->paginate(9);

        /*
         * O primeiro da lista vira o cartao de destaque, mas so na primeira
         * pagina: "destaque" na pagina 3 nao quer dizer nada, e o leitor que ja
         * esta paginando quer varrer titulos, nao um banner por pagina.
         *
         * Com `inFeedOrder`, esse primeiro e a materia marcada como destaque
         * no painel; sem nenhuma marcada, continua sendo a mais recente.
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
        abort_unless($post->isVisibleToPublic(), 404);

        return view('portal.posts.show', $portalData->merge([
            'post' => $post,
            'relatedPosts' => Post::query()
                ->visibleToPublic()
                ->whereKeyNot($post->id)
                ->latest('published_at')
                ->limit(3)
                ->get(),
        ]));
    }
}
