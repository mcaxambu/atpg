<?php

namespace App\Http\Requests;

use App\Enums\ModerationStatus;
use App\Models\Company;
use App\Rules\Cnpj;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicCompanyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['required', 'string', 'max:255'],
            'cnpj' => [
                'required',
                'string',
                new Cnpj,
                Rule::unique('companies', 'cnpj')->whereNull('deleted_at'),
            ],
            'segment' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['required', 'string', 'size:2'],
            'zip_code' => ['required', 'string', 'regex:/^\d{5}-\d{3}$/'],
            'address' => ['required', 'string', 'max:255'],
            'address_number' => ['required', 'string', 'max:32'],
            'neighborhood' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'min:20', 'max:5000'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_role' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'whatsapp' => ['required', 'string', 'regex:/^\(\d{2}\) \d{4,5}-\d{4}$/'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'privacy_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'cnpj.unique' => 'Este CNPJ já possui um cadastro enviado ou publicado no portal.',
            'zip_code.regex' => 'Informe o CEP no formato 00000-000.',
            'whatsapp.regex' => 'Informe o WhatsApp com DDD, por exemplo (42) 99999-9999.',
            'description.min' => 'A descrição precisa ter pelo menos 20 caracteres.',
            'logo.required' => 'Envie a logo da empresa.',
            'privacy_consent.accepted' => 'Confirme a autorização de uso dos dados para análise e publicação após aprovação.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj' => Cnpj::format($this->input('cnpj')),
            'state' => $this->filled('state') ? mb_strtoupper($this->input('state')) : null,
        ]);
    }

    /**
     * Cadastro publico sempre entra como pendente e despublicado.
     */
    public function companyData(): array
    {
        $data = $this->safe()->except(['logo', 'privacy_consent']);

        $data['slug'] = Company::uniqueSlug($data['name']);
        $data['initials'] = mb_strtoupper(mb_substr($data['name'], 0, 3));
        $data['is_active'] = false;
        $data['status'] = ModerationStatus::Pending;
        $data['registration_source'] = 'public';

        return $data;
    }
}
