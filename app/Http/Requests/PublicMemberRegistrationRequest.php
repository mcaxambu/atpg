<?php

namespace App\Http\Requests;

use App\Enums\ModerationStatus;
use App\Http\Requests\Admin\MemberRequest;
use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicMemberRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'company_id' => [
                'nullable',
                Rule::exists('companies', 'id')->where('status', ModerationStatus::Approved->value)->whereNull('deleted_at'),
            ],
            'role' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'specialties' => ['nullable', 'array', 'max:8'],
            'specialties.*' => ['integer', 'exists:specialties,id'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'experiences' => ['nullable', 'string', 'max:2000'],
            'projects' => ['nullable', 'string', 'max:2000'],
            'certifications' => ['nullable', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('members', 'email')->whereNull('deleted_at'),
            ],
            'privacy_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.exists' => 'Selecione uma empresa já publicada no portal.',
            'email.unique' => 'Já existe um cadastro de membro com este e-mail.',
            'privacy_consent.accepted' => 'Confirme a autorização de uso dos dados para análise e publicação após aprovação.',
        ];
    }

    public function memberData(): array
    {
        $data = $this->safe()->except([
            'photo', 'specialties', 'experiences', 'projects', 'certifications', 'privacy_consent',
        ]);

        $data['slug'] = Member::uniqueSlug($data['name']);
        $data['avatar_initials'] = MemberRequest::initials($data['name']);
        $data['avatar_color'] = '#0f4c81';
        $data['city'] = $data['city'] ?? 'Ponta Grossa, PR';
        $data['experience_years'] = $data['experience_years'] ?? 0;
        $data['is_featured'] = false;
        $data['is_active'] = false;
        $data['status'] = ModerationStatus::Pending;
        $data['registration_source'] = 'public';

        return $data;
    }
}
