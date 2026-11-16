<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RadarStatus;
use App\Enums\RadarTipo;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Post;
use App\Models\RadarItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Radar: a fila do que a varredura encontrou.
 *
 * A tela existe para a diretoria DECIDIR, não para o robô publicar. Por isso
 * "publicar" não publica: leva ao formulário de evento ou de notícia já
 * preenchido, para a data ser conferida por gente antes de ir ao portal.
 */
class RadarController extends Controller
{
    public function index(Request $request): View
    {
        $status = RadarStatus::tryFrom((string) $request->query('status')) ?? RadarStatus::Novo;

        $itens = RadarItem::query()
            ->where('status', $status)
            ->doTipo($request->query('tipo'))
            ->orderByRaw('data_inicio is null')
            ->orderBy('data_inicio')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('portal.admin.radar.index', [
            'itens' => $itens,
            'status' => $status,
            'tipo' => $request->query('tipo'),
            'contagens' => $this->contagens(),
            'ultimaVarredura' => RadarItem::max('created_at'),
        ]);
    }

    /**
     * Cria o RASCUNHO e abre o formulário dele para revisão.
     *
     * Não publica: nasce despublicado, e quem liga o interruptor é a diretoria
     * depois de olhar a data. É nela que a leitura automática erra, e ninguém
     * confere o que já está no ar.
     */
    public function publicar(Request $request, RadarItem $radar): RedirectResponse
    {
        abort_if($radar->tipo === RadarTipo::Irrelevante, 404);
        abort_if($radar->status === RadarStatus::Publicado, 404);

        $rascunho = $radar->tipo === RadarTipo::Evento
            ? $this->rascunhoDeEvento($radar)
            : $this->rascunhoDeNoticia($radar);

        $radar->update([
            'status' => RadarStatus::Publicado,
            'decidido_em' => now(),
            'decidido_por' => $request->user()->id,
            'publicado_type' => $rascunho::class,
            'publicado_id' => $rascunho->id,
        ]);

        $destino = $rascunho instanceof Event
            ? route('admin.cms.events.edit', $rascunho)
            : route('admin.cms.posts.edit', $rascunho);

        return redirect()->to($destino)
            ->with('status', 'Rascunho criado a partir do radar. Confira a data e publique quando estiver certo.');
    }

    private function rascunhoDeEvento(RadarItem $radar): Event
    {
        $descricao = trim((string) $radar->resumo);

        return Event::create([
            'title' => $radar->titulo ?: $radar->titulo_original,
            'slug' => Str::slug(Str::limit($radar->titulo ?: $radar->titulo_original, 60, '')).'-'.Str::random(5),
            'type' => 'Evento',
            // O link da fonte vai junto: quem revisa precisa conferir contra o
            // original, e o crédito é de quem publicou primeiro.
            'description' => $descricao."\n\nFonte: ".$radar->nomeDaFonte().' — '.$radar->url,
            'location' => $radar->local,
            'event_date' => $radar->data_inicio,
            'starts_at' => $radar->hora_inicio,
            'registration_url' => $radar->inscricao_url,
            'is_published' => false,
        ]);
    }

    private function rascunhoDeNoticia(RadarItem $radar): Post
    {
        $titulo = $radar->titulo ?: $radar->titulo_original;

        return Post::create([
            'title' => $titulo,
            'slug' => Str::slug(Str::limit($titulo, 60, '')).'-'.Str::random(5),
            'excerpt' => Str::limit((string) $radar->resumo, 280),
            'body' => '<p>'.e((string) $radar->resumo).'</p>',
            'category' => $radar->tipo === RadarTipo::Edital ? 'Editais' : 'Tecnologia',
            'source_url' => $radar->url,
            'source_name' => $radar->nomeDaFonte(),
            'is_published' => false,
        ]);
    }

    public function descartar(Request $request, RadarItem $radar): RedirectResponse
    {
        $radar->update([
            'status' => RadarStatus::Descartado,
            'decidido_em' => now(),
            'decidido_por' => $request->user()->id,
        ]);

        return back()->with('status', 'Item descartado.');
    }

    /**
     * Devolve um descarte para a fila: engano de clique precisa de volta.
     */
    public function restaurar(Request $request, RadarItem $radar): RedirectResponse
    {
        $radar->update([
            'status' => RadarStatus::Novo,
            'decidido_em' => null,
            'decidido_por' => null,
        ]);

        return back()->with('status', 'Item devolvido para a fila.');
    }

    /**
     * @return array<string, int>
     */
    private function contagens(): array
    {
        return [
            RadarStatus::Novo->value => RadarItem::where('status', RadarStatus::Novo)->count(),
            RadarStatus::Publicado->value => RadarItem::where('status', RadarStatus::Publicado)->count(),
            RadarStatus::Descartado->value => RadarItem::where('status', RadarStatus::Descartado)->count(),
            RadarStatus::Ilegivel->value => RadarItem::where('status', RadarStatus::Ilegivel)->count(),
        ];
    }
}
