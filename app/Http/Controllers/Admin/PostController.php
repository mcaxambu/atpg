<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Support\LinkPreview;
use App\Support\RemoteImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index()
    {
        return view('portal.admin.cms.posts.index', [
            'posts' => Post::query()->latest('published_at')->latest()->get(),
        ]);
    }

    public function create()
    {
        return view('portal.admin.cms.posts.form', ['post' => new Post]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('post-covers', 'public');
        } elseif ($capa = $this->capaDoLink($request)) {
            $data['cover_image'] = $capa;
        }

        Post::create($data);

        return redirect()->route('admin.cms.posts.index')->with('status', 'Noticia cadastrada com sucesso.');
    }

    public function edit(Post $post)
    {
        return view('portal.admin.cms.posts.form', compact('post'));
    }

    public function update(Request $request, Post $post)
    {
        $data = $this->validatedData($request, $post);

        if ($request->hasFile('cover_image')) {
            if ($post->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }

            $data['cover_image'] = $request->file('cover_image')->store('post-covers', 'public');
        } elseif ($capa = $this->capaDoLink($request)) {
            if ($post->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }

            $data['cover_image'] = $capa;
        }

        $post->update($data);

        return redirect()->route('admin.cms.posts.index')->with('status', 'Noticia atualizada com sucesso.');
    }

    public function destroy(Post $post)
    {
        if ($post->cover_image) {
            Storage::disk('public')->delete($post->cover_image);
        }

        $post->delete();

        return redirect()->route('admin.cms.posts.index')->with('status', 'Noticia removida com sucesso.');
    }

    /**
     * Baixa a capa sugerida pela pre-visualizacao do link.
     *
     * So e chamado quando o editor NAO enviou um arquivo proprio: o upload
     * manual sempre ganha da imagem vinda do link.
     */
    private function capaDoLink(Request $request): ?string
    {
        $url = $request->input('cover_image_url');

        if (blank($url)) {
            return null;
        }

        return app(RemoteImage::class)->store($url, 'post-covers');
    }

    /**
     * Le titulo, resumo e imagem do link, para o formulario preencher sozinho.
     * Responde JSON: quem chama e o botao "Buscar do link".
     */
    public function preview(Request $request, LinkPreview $preview)
    {
        $dados = $request->validate([
            'url' => ['required', 'string', 'max:1000'],
        ], [], ['url' => 'link']);

        $resultado = $preview->fetch($dados['url']);

        return response()->json($resultado, $resultado['ok'] ? 200 : 422);
    }

    private function validatedData(Request $request, ?Post $post = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'min:20'],
            'category' => ['required', 'string', 'max:80'],
            'source_url' => ['nullable', 'url', 'max:255'],
            'source_name' => ['nullable', 'string', 'max:120'],
            'published_at' => ['nullable', 'date'],
            'is_published' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            // Preenchido pelo botao "Buscar do link": a capa vem do endereco
            // da imagem, e nao de um arquivo enviado pelo navegador.
            'cover_image_url' => ['nullable', 'url', 'max:1000'],
        ]);

        unset($data['cover_image_url']);

        $data['slug'] = $this->uniqueSlug($data['title'], $post);
        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');

        if ($data['is_published'] && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (! $data['is_published']) {
            $data['published_at'] = null;
        }

        return $data;
    }

    private function uniqueSlug(string $title, ?Post $post = null): string
    {
        $baseSlug = Str::slug($title);
        $slug = $baseSlug;
        $counter = 2;

        while (Post::where('slug', $slug)->when($post, fn ($query) => $query->whereKeyNot($post->id))->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
