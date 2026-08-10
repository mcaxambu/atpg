<?php

namespace App\Http\Requests\Company;

use App\Enums\ModerationStatus;
use App\Http\Requests\Admin\MemberRequest;
use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Colaborador cadastrado pela propria empresa.
 *
 * Situacao e publicacao NAO vem do formulario: quem decide se o perfil entra
 * no diretorio e a associacao. Todo envio (novo ou editado) volta para a fila
 * de analise.
 */
class CompanyMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->company_id;
    }

    public function rules(): array
    {
        $memberId = $this->route('member');

        return [
            'name' => ['required', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'specialties' => ['nullable', 'array', 'max:8'],
            'specialties.*' => ['integer', 'exists:specialties,id'],
            'experiences' => ['nullable', 'string', 'max:5000'],
            'projects' => ['nullable', 'string', 'max:5000'],
            'certifications' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('members', 'email')->ignore($memberId)->whereNull('deleted_at'),
            ],
        ];
    }

    public function messages(): array
    {
        return ['email.unique' => 'Já existe um colaborador cadastrado com este e-mail.'];
    }

    public function memberData(?Member $member = null): array
    {
        $data = $this->safe()->except([
            'photo', 'specialties', 'experiences', 'projects', 'certifications',
        ]);

        $data['slug'] = Member::uniqueSlug($data['name'], $member?->id);
        $data['avatar_initials'] = MemberRequest::initials($data['name']);
        $data['avatar_color'] = $member?->avatar_color ?: '#0f4c81';
        $data['experience_years'] = $data['experience_years'] ?? 0;
        $data['registration_source'] = 'company';

        // Toda alteracao volta para a fila: a empresa nao publica sozinha.
        $data['status'] = ModerationStatus::Pending;
        $data['is_active'] = false;
        $data['rejection_reason'] = null;
        $data['reviewed_at'] = null;
        $data['reviewed_by'] = null;

        return $data;
    }
}
