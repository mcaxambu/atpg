<?php

namespace App\Http\Resources;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil profissional publico.
 *
 * E-mail e WhatsApp ficam DE FORA de proposito: eles aparecem na pagina do
 * perfil, mas expor dados de contato pessoal numa API paginada permite coleta
 * em massa, que e exatamente o risco que a LGPD trata. Se a associacao quiser
 * liberar, o caminho e um endpoint autenticado por token.
 *
 * @mixin Member
 */
class MemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'nome' => $this->name,
            'cargo' => $this->role,
            'cidade' => $this->city,
            'anos_experiencia' => $this->experience_years,
            'resumo' => $this->summary,
            'destaque' => $this->is_featured,
            'foto' => $this->photo_path ? asset('storage/'.$this->photo_path) : null,
            'avatar' => [
                'iniciais' => $this->avatar_initials,
                'cor' => $this->avatar_color,
            ],
            'empresa' => $this->whenLoaded('company', fn () => $this->company ? [
                'slug' => $this->company->slug,
                'nome' => $this->company->name,
                // Link pronto para uso: sem isto quem consome a listagem
                // precisaria montar a URL concatenando o slug na mao.
                'url' => route('companies.show', $this->company->slug),
                'api_url' => route('api.v1.companies.show', $this->company->slug),
            ] : null),
            'especialidades' => $this->whenLoaded(
                'specialties',
                fn () => $this->specialties->map(fn ($s) => ['slug' => $s->slug, 'nome' => $s->name])->values()
            ),
            'experiencias' => $this->whenLoaded('experiences', fn () => $this->experiences->pluck('title')),
            'projetos' => $this->whenLoaded('projects', fn () => $this->projects->pluck('title')),
            'certificacoes' => $this->whenLoaded('certifications', fn () => $this->certifications->pluck('title')),
            'links' => array_filter([
                'site' => $this->site_url,
                'linkedin' => $this->linkedin_url,
                'instagram' => $this->instagram_url,
            ]),
            'url' => route('members.show', $this->slug),
            'api_url' => route('api.v1.members.show', $this->slug),
            'atualizado_em' => $this->updated_at?->toAtomString(),
        ];
    }
}
