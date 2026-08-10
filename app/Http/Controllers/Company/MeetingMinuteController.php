<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\MeetingMinute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Atas visiveis para a empresa associada.
 *
 * Somente as publicadas: uma ata em rascunho no painel administrativo nao
 * aparece aqui nem pode ser baixada trocando o id na URL.
 */
class MeetingMinuteController extends Controller
{
    public function index(Request $request): View
    {
        return view('portal.company.atas.index', [
            'atas' => MeetingMinute::query()
                ->published()
                ->search($request->query('q'))
                ->recentFirst()
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    /**
     * Leitura da ata na tela, a partir do texto em Markdown.
     */
    public function show(int $ata): View
    {
        $minuta = MeetingMinute::published()->findOrFail($ata);

        abort_unless($minuta->hasBody(), 404);

        return view('portal.company.atas.show', ['ata' => $minuta]);
    }

    public function download(int $ata)
    {
        $minuta = MeetingMinute::published()->findOrFail($ata);

        // Sem PDF mas com texto: manda ler em vez de devolver 404.
        if (! $minuta->hasFile()) {
            abort_unless($minuta->hasBody(), 404);

            return redirect()->route('empresa.atas.show', $minuta->id);
        }

        abort_unless(Storage::disk(MeetingMinute::DISK)->exists($minuta->file_path), 404);

        return Storage::disk(MeetingMinute::DISK)->download($minuta->file_path, $minuta->file_name);
    }
}
