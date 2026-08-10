<?php

namespace App\Http\Requests\Admin;

use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(MeetingType::class)],
            'status' => ['required', new Enum(MeetingStatus::class)],
            'scheduled_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after:scheduled_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'online_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'confirmations_until' => ['nullable', 'date', 'before_or_equal:scheduled_at'],
            'quorum_minimum' => ['nullable', 'integer', 'min:1', 'max:999'],

            'agenda' => ['nullable', 'array', 'max:60'],
            'agenda.*.id' => ['nullable', 'integer'],
            'agenda.*.title' => ['nullable', 'string', 'max:255'],
            'agenda.*.description' => ['nullable', 'string', 'max:2000'],
            'agenda.*.presenter' => ['nullable', 'string', 'max:120'],
            'agenda.*.duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
        ];
    }

    public function messages(): array
    {
        return [
            'ends_at.after' => 'O término precisa ser depois do início.',
            'confirmations_until.before_or_equal' => 'O prazo de confirmação não pode ser depois da reunião.',
        ];
    }

    public function meetingData(): array
    {
        return $this->safe()->except(['agenda']);
    }

    /**
     * Itens de pauta preenchidos, ja renumerados na ordem enviada.
     * Linhas sem titulo sao descartadas: sao as vazias do formulario.
     *
     * @return array<int, array<string, mixed>>
     */
    public function agendaItems(): array
    {
        return collect($this->input('agenda', []))
            ->filter(fn ($item) => filled($item['title'] ?? null))
            ->values()
            ->map(fn ($item, $indice) => [
                'id' => isset($item['id']) && $item['id'] !== '' ? (int) $item['id'] : null,
                'position' => $indice,
                'title' => $item['title'],
                'description' => $item['description'] ?? null,
                'presenter' => $item['presenter'] ?? null,
                'duration_minutes' => ($item['duration_minutes'] ?? '') !== '' ? (int) $item['duration_minutes'] : null,
            ])
            ->all();
    }
}
