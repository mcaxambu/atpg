<?php

namespace App\Http\Requests\Admin;

use App\Enums\ProspectSource;
use App\Enums\ProspectStage;
use App\Models\Prospect;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ProspectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in([Prospect::KIND_COMPANY, Prospect::KIND_PERSON])],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:255'],
            'segment' => ['nullable', 'string', 'max:255'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'stage' => ['required', new Enum(ProspectStage::class)],
            'source' => ['required', new Enum(ProspectSource::class)],
            'owner_user_id' => ['nullable', 'exists:users,id'],
            'next_action' => ['nullable', 'string', 'max:255'],
            // Data no passado e aceita de proposito: quem cadastra hoje um
            // contato de semana passada precisa registrar o atraso real.
            'next_action_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da empresa ou da pessoa.',
            'email.email' => 'E-mail inválido.',
            'site_url.url' => 'O site precisa começar com http:// ou https://.',
        ];
    }

    /** @return array<string, mixed> */
    public function prospectData(): array
    {
        $dados = $this->safe()->all();

        // Sem responsavel definido, quem cadastrou assume: prospecto sem dono
        // e prospecto que ninguem cobra.
        $dados['owner_user_id'] = $dados['owner_user_id'] ?? $this->user()->id;

        return $dados;
    }
}
