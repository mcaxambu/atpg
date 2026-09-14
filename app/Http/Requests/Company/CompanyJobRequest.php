<?php

namespace App\Http\Requests\Company;

use App\Enums\JobType;
use App\Enums\JobWorkplace;
use App\Enums\ModerationStatus;
use App\Models\JobOpening;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Vaga cadastrada pela propria empresa.
 *
 * Situacao e publicacao NAO vem do formulario: quem decide se a vaga entra no
 * portal e a associacao. Todo envio (nova ou editada) volta para a fila.
 */
class CompanyJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->company_id;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(JobType::class)],
            'workplace' => ['required', Rule::enum(JobWorkplace::class)],
            'seniority' => ['nullable', 'string', 'max:32'],
            'description' => ['required', 'string', 'min:40', 'max:8000'],
            'requirements' => ['nullable', 'string', 'max:5000'],
            'benefits' => ['nullable', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:1000000', 'gte:salary_min'],
            'show_salary' => ['nullable', 'boolean'],
            'closes_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }

    public function messages(): array
    {
        return [
            'salary_max.gte' => 'O teto da faixa salarial não pode ser menor que o piso.',
            'closes_at.after_or_equal' => 'A data de encerramento não pode estar no passado.',
            'description.min' => 'Descreva a vaga com pelo menos 40 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'type' => 'tipo de contrato',
            'workplace' => 'modelo de trabalho',
            'description' => 'descrição',
            'closes_at' => 'data de encerramento',
            'salary_min' => 'piso salarial',
            'salary_max' => 'teto salarial',
        ];
    }

    public function jobData(?JobOpening $job = null): array
    {
        $data = $this->safe()->all();

        $data['slug'] = JobOpening::uniqueSlug($data['title'], $job?->id);
        $data['state'] = filled($data['state'] ?? null) ? mb_strtoupper($data['state']) : null;
        $data['show_salary'] = $this->boolean('show_salary');

        // Sem faixa informada nao ha o que exibir, mesmo que a caixa esteja marcada.
        if (blank($data['salary_min'] ?? null) && blank($data['salary_max'] ?? null)) {
            $data['show_salary'] = false;
        }

        // Toda alteracao voltar para a fila: a empresa nao publica sozinha.
        $data['status'] = ModerationStatus::Pending;
        $data['is_active'] = false;
        $data['rejection_reason'] = null;
        $data['reviewed_at'] = null;
        $data['reviewed_by'] = null;

        return $data;
    }
}
