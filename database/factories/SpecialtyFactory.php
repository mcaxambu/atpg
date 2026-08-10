<?php

namespace Database\Factories;

use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Specialty>
 */
class SpecialtyFactory extends Factory
{
    protected $model = Specialty::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement([
            'Back-end', 'Front-end', 'Mobile', 'DevOps', 'Dados', 'Design', 'Segurança', 'QA',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
