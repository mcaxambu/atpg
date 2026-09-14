<?php

namespace Database\Factories;

use App\Enums\JobType;
use App\Enums\JobWorkplace;
use App\Enums\ModerationStatus;
use App\Models\Company;
use App\Models\JobOpening;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobOpening>
 */
class JobOpeningFactory extends Factory
{
    protected $model = JobOpening::class;

    public function definition(): array
    {
        $title = $this->faker->jobTitle();

        return [
            'company_id' => Company::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(5),
            'type' => JobType::Clt,
            'workplace' => JobWorkplace::OnSite,
            'seniority' => 'Pleno',
            'description' => $this->faker->paragraphs(3, true),
            'requirements' => "Experiência com PHP\nGit no dia a dia",
            'benefits' => "Vale-refeição\nPlano de saúde",
            'city' => 'Ponta Grossa',
            'state' => 'PR',
            'salary_min' => 4000,
            'salary_max' => 7000,
            'show_salary' => true,
            'closes_at' => null,
            'status' => ModerationStatus::Approved,
            'is_active' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => ModerationStatus::Pending,
            'is_active' => false,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => ModerationStatus::Rejected,
            'is_active' => false,
            'rejection_reason' => 'Descrição insuficiente.',
        ]);
    }

    /** Prazo ja vencido: nao deve aparecer no portal. */
    public function expired(): static
    {
        return $this->state(fn () => [
            'closes_at' => now()->subDay()->toDateString(),
        ]);
    }
}
