<?php

namespace App\Http\Requests\Company;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A empresa edita a propria vitrine e publica direto.
 *
 * Nome, razao social e CNPJ ficam FORA: sao o que a associacao aprovou, e
 * deixar a empresa troca-los depois permitiria um cadastro aprovado virar
 * outra coisa sem nova analise. Alteracao desses campos passa pelo admin.
 */
class CompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->company;
    }

    public function rules(): array
    {
        return [
            'segment' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:16'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:32'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_role' => ['nullable', 'string', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('state')) {
            $this->merge(['state' => mb_strtoupper($this->input('state'))]);
        }
    }

    public function profileData(Company $company): array
    {
        return $this->safe()->except(['logo']);
    }
}
