<?php

namespace Database\Factories;

use App\Enums\MeetingStatus;
use App\Enums\MeetingType;
use App\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    public function definition(): array
    {
        return [
            'title' => 'Assembleia de '.$this->faker->monthName(),
            'type' => MeetingType::AssembleiaOrdinaria,
            'status' => MeetingStatus::Agendada,
            'scheduled_at' => now()->addWeek()->setTime(19, 0),
            'ends_at' => now()->addWeek()->setTime(21, 0),
            'location' => 'Sede da associação',
            'description' => $this->faker->sentence(10),
        ];
    }

    public function passada(): static
    {
        return $this->state(fn () => [
            'scheduled_at' => now()->subWeek()->setTime(19, 0),
            'ends_at' => now()->subWeek()->setTime(21, 0),
        ]);
    }

    public function realizada(): static
    {
        return $this->passada()->state(fn () => ['status' => MeetingStatus::Realizada]);
    }

    public function cancelada(): static
    {
        return $this->state(fn () => ['status' => MeetingStatus::Cancelada]);
    }
}
