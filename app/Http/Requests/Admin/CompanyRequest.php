<?php

namespace App\Http\Requests\Admin;

use App\Enums\ModerationStatus;
use App\Models\Company;
use App\Rules\Cnpj;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $company = $this->route('company');

        return [
            'name' => ['required', 'string', 'max:255'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => [
                'nullable',
                'string',
                new Cnpj,
                Rule::unique('companies', 'cnpj')->ignore($company)->whereNull('deleted_at'),
            ],
            'initials' => ['nullable', 'string', 'max:8'],
            'segment' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'zip_code' => ['nullable', 'string', 'max:16'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_number' => ['nullable', 'string', 'max:32'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_role' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', new Enum(ModerationStatus::class)],
            'rejection_reason' => ['nullable', 'string', 'max:1000', 'required_if:status,'.ModerationStatus::Rejected->value],
            'is_active' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cnpj' => Cnpj::format($this->input('cnpj')),
            'state' => $this->filled('state') ? mb_strtoupper($this->input('state')) : null,
            'status' => $this->input('status') ?: ModerationStatus::Pending->value,
        ]);
    }

    /**
     * Dados prontos para persistir, ja com slug unico e situacao coerente.
     */
    public function companyData(?Company $company = null): array
    {
        $data = $this->safe()->except(['logo']);

        $data['slug'] = Company::uniqueSlug($data['name'], $company?->id);
        $data['is_active'] = $data['status'] === ModerationStatus::Approved->value && $this->boolean('is_active');

        if ($data['status'] !== ModerationStatus::Rejected->value) {
            $data['rejection_reason'] = null;
        }

        // Registra quem decidiu sempre que a situacao muda.
        if ($company?->status?->value !== $data['status']) {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] = $this->user()?->id;
        }

        return $data;
    }
}
