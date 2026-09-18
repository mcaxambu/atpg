<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Columnist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Foto propria do colunista, trocada pelo painel dele.
 *
 * Um controller so para empresa e membro, como o das colunas: o colunista vem
 * sempre de `$request->user()->columnist()`, entao nao ha id na URL para trocar
 * e alcancar a foto de outro autor.
 *
 * Sem foto propria, o portal volta a mostrar a do cadastro (logo da empresa ou
 * foto do membro) — ver Columnist::getPhotoPathAttribute.
 */
class ColumnistPhotoController extends Controller
{
    private const DIRETORIO = 'columnists';

    public function update(Request $request): RedirectResponse
    {
        $colunista = $this->colunista($request);

        $request->validate(
            [
                // Sem SVG: o arquivo fica servido no dominio do portal, e SVG
                // pode carregar script dentro.
                'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:3072', 'dimensions:min_width=120,min_height=120'],
            ],
            [
                'photo.required' => 'Escolha uma foto para enviar.',
                'photo.image' => 'O arquivo enviado não é uma imagem.',
                'photo.mimes' => 'A foto deve ser JPG, PNG ou WEBP.',
                'photo.max' => 'A foto pode ter no máximo 3 MB.',
                'photo.dimensions' => 'A foto precisa ter pelo menos 120 × 120 pixels.',
            ]
        );

        $anterior = $colunista->custom_photo_path;

        $colunista->update([
            'custom_photo_path' => $request->file('photo')->store(self::DIRETORIO, 'public'),
        ]);

        // Apaga a antiga so depois de gravar a nova: se o envio falhasse no
        // meio, o colunista ficaria sem foto nenhuma.
        if ($anterior) {
            Storage::disk('public')->delete($anterior);
        }

        return $this->voltar($request, 'Foto atualizada. Ela já aparece nas suas colunas no portal.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $colunista = $this->colunista($request);

        if ($colunista->custom_photo_path) {
            Storage::disk('public')->delete($colunista->custom_photo_path);
            $colunista->update(['custom_photo_path' => null]);
        }

        return $this->voltar($request, 'Foto removida. O portal volta a usar '.$colunista->fallbackPhotoLabel().'.');
    }

    private function colunista(Request $request): Columnist
    {
        $colunista = $request->user()->columnist();

        abort_unless($colunista, 403, 'Seu cadastro não está marcado como colunista.');

        return $colunista;
    }

    private function voltar(Request $request, string $mensagem): RedirectResponse
    {
        $rota = $request->user()->isCompany() ? 'empresa.colunas.index' : 'membro.colunas.index';

        return redirect()->route($rota)->with('status', $mensagem);
    }
}
