<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Candidatura enviada pelo site publico.
 *
 * O consentimento e campo obrigatorio e vira data no banco: sem ele nao ha
 * base legal para guardar o curriculo de alguem.
 */
class JobApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $jobId = $this->route('job')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                // Uma candidatura por pessoa em cada vaga.
                Rule::unique('job_applications', 'email')
                    ->where(fn ($query) => $query->where('job_opening_id', $jobId)),
            ],
            'phone' => ['nullable', 'string', 'max:40'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
            'message' => ['nullable', 'string', 'max:3000'],
            'resume' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Você já se candidatou a esta vaga.',
            'resume.required' => 'Anexe seu currículo em PDF, DOC ou DOCX.',
            'resume.mimes' => 'O currículo deve ser PDF, DOC ou DOCX.',
            'resume.max' => 'O currículo deve ter no máximo 5 MB.',
            'consent.accepted' => 'É preciso autorizar o envio dos seus dados para a empresa.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'email' => 'e-mail',
            'phone' => 'telefone',
            'message' => 'mensagem',
            'resume' => 'currículo',
        ];
    }

    public function applicationData(): array
    {
        return [
            'name' => $this->input('name'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'linkedin_url' => $this->input('linkedin_url'),
            'message' => $this->input('message'),
            'consented_at' => now(),
        ];
    }
}
