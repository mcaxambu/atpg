<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    public function definition(): array
    {
        return [
            'job_opening_id' => JobOpening::factory(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => '42999990000',
            'message' => $this->faker->sentence(),
            'resume_path' => null,
            'consented_at' => now(),
        ];
    }
}
