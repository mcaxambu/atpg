<?php

namespace App\Http\Requests\Admin;

use App\Enums\ModerationStatus;
use App\Models\Member;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;

class MemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['nullable', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'avatar_initials' => ['nullable', 'string', 'max:8'],
            'avatar_color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'role' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'specialties' => ['nullable', 'array'],
            'specialties.*' => ['integer', 'exists:specialties,id'],
            'experiences' => ['nullable', 'string', 'max:5000'],
            'projects' => ['nullable', 'string', 'max:5000'],
            'certifications' => ['nullable', 'string', 'max:5000'],
            'site_url' => ['nullable', 'url', 'max:255'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'instagram_url' => ['nullable', 'url', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'status' => ['required', new Enum(ModerationStatus::class)],
            'rejection_reason' => ['nullable', 'string', 'max:1000', 'required_if:status,'.ModerationStatus::Rejected->value],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->input('status') ?: ModerationStatus::Pending->value,
        ]);
    }

    public function memberData(?Member $member = null): array
    {
        $data = $this->safe()->except(['photo', 'specialties', 'experiences', 'projects', 'certifications']);

        // safe() só traz as chaves que vieram na requisição; campos opcionais
        // ausentes precisam de valor padrão explícito.
        $data['slug'] = Member::uniqueSlug($data['name'], $member?->id);
        $data['avatar_initials'] = ($data['avatar_initials'] ?? null) ?: self::initials($data['name']);
        $data['avatar_color'] = ($data['avatar_color'] ?? null) ?: '#0f4c81';
        $data['experience_years'] = $data['experience_years'] ?? 0;
        $data['is_featured'] = $this->boolean('is_featured');
        $data['is_active'] = $data['status'] === ModerationStatus::Approved->value && $this->boolean('is_active');

        if ($data['status'] !== ModerationStatus::Rejected->value) {
            $data['rejection_reason'] = null;
        }

        if ($member?->status?->value !== $data['status']) {
            $data['reviewed_at'] = now();
            $data['reviewed_by'] = $this->user()?->id;
        }

        return $data;
    }

    public static function initials(string $name): string
    {
        return Str::of($name)->explode(' ')->filter()->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }
}
