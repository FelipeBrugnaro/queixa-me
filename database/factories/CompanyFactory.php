<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Companies\Enums\CompanyStatus;
use App\Domain\Companies\Models\Company;
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
        $name = fake()->unique()->company();

        return [
            'uuid' => (string) Str::uuid(),
            'name' => $name,
            'legal_name' => $name.', '.fake()->randomElement(['Lda.', 'S.A.', 'Unipessoal Lda.']),
            'slug' => Company::generateSlug($name),
            'status' => CompanyStatus::Active,
            'description' => fake()->paragraph(3),
            'website' => 'https://'.Str::slug($name).'.pt',
            'support_email' => 'apoio@'.Str::slug($name).'.pt',
            'country' => 'PT',
            'district' => fake()->randomElement(['Lisboa', 'Porto', 'Braga', 'Faro', 'Coimbra', 'Setúbal', 'Aveiro']),
            'locality' => fake()->city(),
            'accepts_complaints' => true,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => CompanyStatus::Pending, 'is_indexable' => false]);
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => CompanyStatus::Verified,
            'verified_at' => now(),
            'claimed_at' => now(),
        ]);
    }
}
