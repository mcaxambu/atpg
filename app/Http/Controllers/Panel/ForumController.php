<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Mail\ForumReplyMail;
use App\Models\ForumCategory;
use App\Models\ForumReply;
use App\Models\ForumTopic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Forum dos associados.
 *
 * Um controller so atende empresa e membro, como o das colunas: muda apenas o
 * prefixo da rota e o layout, entao duplicar a classe seria duplicar a regra.
 *
 * O forum e FECHADO — nada aqui aparece no site publico. Foi decisao do
 * usuario: o pessoal escreve sabendo que fica entre associados.
 */
class ForumController extends Controller
{
    public function index(Request $request): View
    {
        $categoria = $request->query('categoria')
            ? ForumCategory::where('slug', $request->query('categoria'))->first()
            : null;

        $topicos = ForumTopic::query()
            ->visiveis()
            ->with(['author.company', 'author.member', 'category'])
            ->when($categoria, fn ($q) => $q->where('forum_category_id', $categoria->id))
            ->when($busca = trim((string) $request->query('q')), fn ($q) => $q->where(function ($inner) use ($busca) {
                $inner->where('title', 'like', "%{$busca}%")->orWhere('body', 'like', "%{$busca}%");
            }))
            ->porAtividade()
            ->paginate(20)
            ->withQueryString();

        return view('portal.panel.forum.index', $this->comum($request) + [
            'categorias' => ForumCategory::ativas()->emOrdem()->withCount(['topics' => fn ($q) => $q->visiveis()])->get(),
            'topicos' => $topicos,
            'categoria' => $categoria,
            'busca' => $busca,
        ]);
    }

    public function create(Request $request): View
    {
        return view('portal.panel.forum.form', $this->comum($request) + [
            'topic' => new ForumTopic,
            'categorias' => ForumCategory::ativas()->emOrdem()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $this->validar($request);

        $topico = ForumTopic::create($dados + [
            'user_id' => $request->user()->id,
            'slug' => ForumTopic::uniqueSlug($dados['title']),
        ]);

        return redirect()->route($this->rotaBase($request).'.show', $topico->slug)
            ->with('status', 'Tópico publicado.');
    }

    public function show(Request $request, string $slug): View
    {
        $topico = ForumTopic::visiveis()
            ->with(['category', 'author.company', 'author.member', 'replies.author.company', 'replies.author.member'])
            ->where('slug', $slug)
            ->firstOrFail();

        return view('portal.panel.forum.show', $this->comum($request) + [
            'topic' => $topico,
            'respostas' => $topico->replies()->visiveis()->get(),
        ]);
    }

    public function edit(Request $request, string $slug): View
    {
        $topico = ForumTopic::where('slug', $slug)->firstOrFail();

        abort_unless($topico->podeSerEditadoPor($request->user()), 403);

        return view('portal.panel.forum.form', $this->comum($request) + [
            'topic' => $topico,
            'categorias' => ForumCategory::ativas()->emOrdem()->get(),
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $topico = ForumTopic::where('slug', $slug)->firstOrFail();

        abort_unless($topico->podeSerEditadoPor($request->user()), 403);

        $topico->update($this->validar($request));

        return redirect()->route($this->rotaBase($request).'.show', $topico->slug)
            ->with('status', 'Tópico atualizado.');
    }

    /**
     * Responde e avisa quem acompanha.
     *
     * A contagem e a data da ultima resposta ficam gravadas no topico: a lista
     * do forum ordena por isso e e lida muito mais vezes do que escrita.
     */
    public function reply(Request $request, string $slug): RedirectResponse
    {
        $topico = ForumTopic::visiveis()->where('slug', $slug)->firstOrFail();

        abort_if($topico->is_closed, 403, 'Este tópico está fechado para novas respostas.');

        $dados = $request->validate(
            ['body' => ['required', 'string', 'max:20000']],
            ['body.required' => 'Escreva sua resposta.']
        );

        $resposta = $topico->replies()->create([
            'user_id' => $request->user()->id,
            'body' => $dados['body'],
        ]);

        $topico->forceFill([
            'replies_count' => $topico->replies()->visiveis()->count(),
            'last_reply_at' => now(),
        ])->save();

        $this->avisarParticipantes($topico, $resposta, $request);

        return redirect()->route($this->rotaBase($request).'.show', $topico->slug)
            ->with('status', 'Resposta publicada.');
    }

    public function updateReply(Request $request, ForumReply $reply): RedirectResponse
    {
        abort_unless($reply->podeSerEditadaPor($request->user()), 403);

        $reply->update($request->validate(['body' => ['required', 'string', 'max:20000']]));

        return redirect()->route($this->rotaBase($request).'.show', $reply->topic->slug)
            ->with('status', 'Resposta atualizada.');
    }

    /**
     * Aviso por e-mail de resposta nova.
     *
     * Falha de e-mail NAO pode derrubar a resposta: o texto ja esta publicado,
     * e perder isso por causa do servidor de e-mail seria trocar um problema
     * pequeno por um grande.
     */
    private function avisarParticipantes(ForumTopic $topico, ForumReply $resposta, Request $request): void
    {
        $destinatarios = $topico->participantes()
            ->reject(fn ($user) => $user->id === $request->user()->id);

        foreach ($destinatarios as $user) {
            try {
                Mail::to($user->email)->send(new ForumReplyMail($topico, $resposta, $user));
            } catch (\Throwable $erro) {
                report($erro);
            }
        }
    }

    /** @return array<string, mixed> */
    private function validar(Request $request): array
    {
        return $request->validate(
            [
                'forum_category_id' => ['required', 'exists:forum_categories,id'],
                'title' => ['required', 'string', 'max:160'],
                'body' => ['required', 'string', 'max:20000'],
            ],
            [
                'forum_category_id.required' => 'Escolha o assunto do tópico.',
                'title.required' => 'Dê um título ao tópico.',
                'body.required' => 'Escreva o conteúdo do tópico.',
            ]
        );
    }

    /** @return array<string, mixed> */
    private function comum(Request $request): array
    {
        return [
            'rotaBase' => $this->rotaBase($request),
            'layout' => $request->user()->isCompany() ? 'layouts.company' : 'layouts.membro',
        ];
    }

    private function rotaBase(Request $request): string
    {
        return $request->user()->isCompany() ? 'empresa.forum' : 'membro.forum';
    }
}
