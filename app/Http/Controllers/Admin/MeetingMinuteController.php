<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MeetingMinuteRequest;
use App\Models\MeetingMinute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class MeetingMinuteController extends Controller
{
    public function index(Request $request): View
    {
        return view('portal.admin.atas.index', [
            'atas' => MeetingMinute::query()
                ->with('uploader:id,name')
                ->search($request->query('q'))
                ->recentFirst()
                ->paginate(20)
                ->withQueryString(),
            'total' => MeetingMinute::count(),
        ]);
    }

    public function create(): View
    {
        return view('portal.admin.atas.form', ['ata' => new MeetingMinute(['is_published' => true])]);
    }

    public function store(MeetingMinuteRequest $request): RedirectResponse
    {
        $data = $request->minuteData();
        $data['uploaded_by'] = $request->user()->id;

        if ($request->hasFile('file')) {
            $data += $request->storeFile();
        }

        $ata = MeetingMinute::create($data);

        return redirect()->route('admin.atas.index')
            ->with('status', "Ata \"{$ata->title}\" publicada para as empresas associadas.".$this->avisoDeImagens($request));
    }

    /**
     * Leitura da ata no painel, a partir do texto em Markdown.
     */
    public function show(MeetingMinute $ata): View
    {
        abort_unless($ata->hasBody(), 404);

        $ata->load('uploader:id,name');

        return view('portal.admin.atas.show', compact('ata'));
    }

    public function edit(MeetingMinute $ata): View
    {
        return view('portal.admin.atas.form', compact('ata'));
    }

    public function update(MeetingMinuteRequest $request, MeetingMinute $ata): RedirectResponse
    {
        $data = $request->minuteData();

        if ($request->hasFile('file')) {
            $this->deleteFile($ata);
            $data += $request->storeFile();
        } elseif ($request->boolean('remove_file')) {
            $this->deleteFile($ata);
            $data += ['file_path' => null, 'file_name' => null, 'file_size' => 0];
        }

        $ata->update($data);

        return redirect()->route('admin.atas.index')
            ->with('status', "Ata \"{$ata->title}\" atualizada.".$this->avisoDeImagens($request));
    }

    public function destroy(MeetingMinute $ata): RedirectResponse
    {
        // Exclusao logica: o PDF so sai do disco na exclusao definitiva.
        $ata->delete();

        return redirect()->route('admin.atas.index')
            ->with('status', "Ata \"{$ata->title}\" removida.");
    }

    /**
     * Download pelo painel administrativo. O arquivo vive fora de public/,
     * entao so chega ao navegador por aqui.
     */
    public function download(MeetingMinute $ata)
    {
        // Ata sem PDF pode ter o texto em Markdown: levar para a leitura e
        // mais util do que devolver um 404 seco.
        if (! $ata->hasFile()) {
            abort_unless($ata->hasBody(), 404);

            return redirect()->route('admin.atas.show', $ata);
        }

        abort_unless(Storage::disk(MeetingMinute::DISK)->exists($ata->file_path), 404);

        return Storage::disk(MeetingMinute::DISK)->download($ata->file_path, $ata->file_name);
    }

    /**
     * O Notion exporta imagens como arquivos numa pasta ao lado do .md. Elas
     * nao vem no upload, entao sao descartadas — e o operador precisa saber,
     * senao a ata some um trecho sem explicacao.
     */
    private function avisoDeImagens(MeetingMinuteRequest $request): string
    {
        $total = $request->imagensRemovidas;

        if ($total === 0) {
            return '';
        }

        return $total === 1
            ? ' Uma imagem do arquivo Markdown foi descartada: o Notion a exporta como arquivo separado.'
            : " {$total} imagens do arquivo Markdown foram descartadas: o Notion as exporta como arquivos separados.";
    }

    private function deleteFile(MeetingMinute $ata): void
    {
        if ($ata->file_path && Storage::disk(MeetingMinute::DISK)->exists($ata->file_path)) {
            Storage::disk(MeetingMinute::DISK)->delete($ata->file_path);
        }
    }
}
