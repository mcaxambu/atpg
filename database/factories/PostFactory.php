<?php

namespace Database\Factories;

use App\Enums\ModerationStatus;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        $titulo = $this->faker->unique()->sentence(6);

        return [
            'title' => $titulo,
            'slug' => Str::slug($titulo).'-'.Str::random(5),
            'excerpt' => $this->faker->sentence(12),
            'body' => $this->faker->paragraphs(4, true),
            'category' => 'Notícias',
            'columnist_id' => null,
            // Publicada e aprovada: e o estado util na maioria dos testes.
            'status' => ModerationStatus::Approved,
            'is_published' => true,
            'is_featured' => false,
            'published_at' => now()->subDay(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ModerationStatus::Pending,
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn () => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}
