<?php

namespace Database\Factories;

use App\Models\MeetingMinute;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MeetingMinute>
 */
class MeetingMinuteFactory extends Factory
{
    protected $model = MeetingMinute::class;

    public function definition(): array
    {
        return [
            'title' => 'Assembleia de '.$this->faker->monthName(),
            'meeting_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'summary' => $this->faker->sentence(12),
            'file_path' => MeetingMinute::DIRECTORY.'/'.Str::random(40).'.pdf',
            'file_name' => 'ata-'.Str::random(6).'.pdf',
            'file_size' => $this->faker->numberBetween(50_000, 3_000_000),
            'is_published' => true,
        ];
    }

    public function rascunho(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
