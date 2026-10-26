<?php

namespace Database\Factories;

use App\Models\ForumCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ForumCategory>
 */
class ForumCategoryFactory extends Factory
{
    protected $model = ForumCategory::class;

    public function definition(): array
    {
        $nome = $this->faker->unique()->words(2, true);

        return [
            'name' => Str::ucfirst($nome),
            'slug' => Str::slug($nome).'-'.Str::random(5),
            'description' => 'Assunto de teste do fórum.',
            'position' => 0,
            'is_active' => true,
        ];
    }

    public function inativa(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
