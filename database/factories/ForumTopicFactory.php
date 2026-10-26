<?php

namespace Database\Factories;

use App\Models\ForumCategory;
use App\Models\ForumTopic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ForumTopic>
 */
class ForumTopicFactory extends Factory
{
    protected $model = ForumTopic::class;

    public function definition(): array
    {
        $titulo = Str::ucfirst($this->faker->unique()->sentence(5));

        return [
            'forum_category_id' => ForumCategory::factory(),
            'user_id' => User::factory(),
            'title' => $titulo,
            'slug' => Str::slug($titulo).'-'.Str::random(5),
            'body' => '<p>Conteúdo de teste do tópico.</p>',
            'is_pinned' => false,
            'is_closed' => false,
            'is_hidden' => false,
            'replies_count' => 0,
        ];
    }

    public function fixado(): static
    {
        return $this->state(fn () => ['is_pinned' => true]);
    }

    public function fechado(): static
    {
        return $this->state(fn () => ['is_closed' => true]);
    }

    public function oculto(): static
    {
        return $this->state(fn () => ['is_hidden' => true]);
    }
}
