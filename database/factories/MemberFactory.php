<?php

namespace Database\Factories;

use App\Enums\ModerationStatus;
use App\Models\Company;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->name();

        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::random(5),
            'avatar_initials' => Str::upper(Str::substr($name, 0, 2)),
            'avatar_color' => '#0f4c81',
            'role' => $this->faker->jobTitle(),
            'city' => 'Ponta Grossa, PR',
            'experience_years' => $this->faker->numberBetween(1, 25),
            'summary' => $this->faker->paragraph(),
            'email' => $this->faker->unique()->safeEmail(),
            'registration_source' => 'admin',
            'status' => ModerationStatus::Approved,
            'is_active' => true,
            'is_featured' => false,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ModerationStatus::Pending,
            'is_active' => false,
            'registration_source' => 'public',
        ]);
    }

    public function independent(): static
    {
        return $this->state(fn () => ['company_id' => null]);
    }
}
