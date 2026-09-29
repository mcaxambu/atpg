<?php

namespace Database\Factories;

use App\Enums\ProspectSource;
use App\Enums\ProspectStage;
use App\Models\Prospect;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Prospect>
 */
class ProspectFactory extends Factory
{
    protected $model = Prospect::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'kind' => Prospect::KIND_COMPANY,
            'contact_name' => $this->faker->name(),
            'email' => $this->faker->unique()->companyEmail(),
            'whatsapp' => '(42) 99999-9999',
            'city' => 'Ponta Grossa',
            'segment' => 'Software',
            'stage' => ProspectStage::Novo,
            'source' => ProspectSource::Indicacao,
            'stage_changed_at' => now(),
        ];
    }

    public function naEtapa(ProspectStage $etapa): static
    {
        return $this->state(fn () => ['stage' => $etapa]);
    }

    /** Com o próximo passo vencido: é o que o funil precisa destacar. */
    public function atrasado(): static
    {
        return $this->state(fn () => [
            'stage' => ProspectStage::Contato,
            'next_action' => 'Retornar a ligação',
            'next_action_at' => now()->subDays(3)->toDateString(),
        ]);
    }

    public function parado(int $dias = 30): static
    {
        return $this->state(fn () => [
            'stage' => ProspectStage::Contato,
            'stage_changed_at' => now()->subDays($dias),
            'created_at' => now()->subDays($dias),
        ]);
    }
}
