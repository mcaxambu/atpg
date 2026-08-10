<?php

namespace App\Http\Resources;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Company
 */
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'nome' => $this->name,
            'razao_social' => $this->legal_name,
            'cnpj' => $this->cnpj,
            'segmento' => $this->segment,
            'descricao' => $this->description,
            'cidade' => $this->city,
            'estado' => $this->state,
            'bairro' => $this->neighborhood,
            'site' => $this->site_url,
            'logo' => $this->logo_path ? asset('storage/'.$this->logo_path) : null,
            'total_membros' => $this->whenCounted('members'),
            'membros' => MemberResource::collection($this->whenLoaded('members')),
            // url  = pagina da empresa no portal (para o usuario final)
            // api_url = este mesmo recurso na API (para navegar programaticamente)
            'url' => route('companies.show', $this->slug),
            'api_url' => route('api.v1.companies.show', $this->slug),
            'atualizado_em' => $this->updated_at?->toAtomString(),
        ];
    }
}
