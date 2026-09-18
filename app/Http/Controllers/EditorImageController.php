<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recebe a imagem colocada dentro do texto pelo editor do painel.
 *
 * Atende os tres paineis (diretoria, empresa e membro): a coluna e escrita por
 * membro ou empresa, a vaga pela empresa e a noticia pela diretoria, e todos
 * usam o mesmo editor. Por isso a rota exige apenas sessao — quem esta dentro
 * de um painel ja passou pela autenticacao do papel dele.
 *
 * SVG nao entra na lista de tipos de proposito: e um formato que pode carregar
 * script dentro, e o arquivo fica servido no proprio dominio do portal.
 */
class EditorImageController extends Controller
{
    private const MAXIMO_KB = 5120;

    public function __invoke(Request $request): JsonResponse
    {
        $request->validate(
            [
                'image' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp', 'max:'.self::MAXIMO_KB],
            ],
            [
                'image.required' => 'Escolha uma imagem para enviar.',
                'image.image' => 'O arquivo enviado não é uma imagem.',
                'image.mimes' => 'A imagem deve ser JPG, PNG, GIF ou WEBP.',
                'image.max' => 'A imagem pode ter no máximo 5 MB.',
            ]
        );

        $caminho = $request->file('image')->store('editor', 'public');

        /*
         * `asset()` e nao `Storage::url()`: o portal tambem roda sob o prefixo
         * /atpg, e so o `asset()` respeita a raiz resolvida em runtime. O
         * endereco fica gravado dentro do texto, entao precisa estar completo.
         */
        return response()->json(['url' => asset('storage/'.$caminho)]);
    }
}
