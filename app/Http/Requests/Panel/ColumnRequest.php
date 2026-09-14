<?php

namespace App\Http\Requests\Panel;

use App\Enums\ModerationStatus;
use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Coluna escrita pelo proprio colunista, no painel dele.
 *
 * Publicacao e situacao NAO vem do formulario: quem decide se o texto entra no
 * portal e a associacao. Todo envio, novo ou editado, volta para a fila.
 */
class ColumnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->columnist() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'min:200'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_cover' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'body.min' => 'A coluna precisa de pelo menos 200 caracteres.',
            'title.required' => 'Dê um título à coluna.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'excerpt' => 'resumo',
            'body' => 'texto',
            'cover_image' => 'imagem de capa',
        ];
    }

    public function columnData(?Post $post = null): array
    {
        $data = $this->safe()->except(['cover_image', 'remove_cover']);

        $data['slug'] = $this->slugUnico($data['title'], $post);
        $data['category'] = 'Coluna';

        // Volta para analise a cada envio; a diretoria e quem publica.
        $data['status'] = ModerationStatus::Pending;
        $data['is_published'] = false;
        $data['published_at'] = null;
        $data['rejection_reason'] = null;
        $data['reviewed_at'] = null;
        $data['reviewed_by'] = null;

        return $data;
    }

    private function slugUnico(string $titulo, ?Post $post): string
    {
        $base = str($titulo)->slug()->value() ?: 'coluna';
        $slug = $base;
        $contador = 2;

        while (Post::where('slug', $slug)->when($post, fn ($q) => $q->whereKeyNot($post->id))->exists()) {
            $slug = "{$base}-{$contador}";
            $contador++;
        }

        return $slug;
    }
}
