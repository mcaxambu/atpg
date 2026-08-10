<?php

namespace Database\Factories;

use App\Enums\ModerationStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->company();

        return [
            'name' => $name,
            'legal_name' => $name.' LTDA',
            'cnpj' => $this->faker->unique()->numerify('##.###.###/0001-##'),
            'slug' => Str::slug($name).'-'.Str::random(5),
            'initials' => Str::upper(Str::substr($name, 0, 3)),
            'segment' => $this->faker->randomElement(['Software', 'Infraestrutura', 'Dados', 'Design']),
            'city' => 'Ponta Grossa',
            'state' => 'PR',
            'zip_code' => '84000-000',
            'address' => $this->faker->streetName(),
            'address_number' => (string) $this->faker->buildingNumber(),
            'neighborhood' => 'Centro',
            'description' => $this->faker->paragraph(),
            'contact_name' => $this->faker->name(),
            'contact_role' => 'Diretor',
            'email' => $this->faker->unique()->companyEmail(),
            'whatsapp' => '(42) 99999-9999',
            'site_url' => 'https://exemplo.com.br',
            'registration_source' => 'admin',
            'status' => ModerationStatus::Approved,
            'is_active' => true,
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

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => ModerationStatus::Rejected,
            'is_active' => false,
            'rejection_reason' => 'Dados cadastrais inconsistentes.',
        ]);
    }
}
