<?php

namespace Database\Factories\TaskManagement;

use App\Modules\TaskManagement\Enums\CompanyStatus;
use App\Modules\TaskManagement\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /** @var class-string<Company> */
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => Str::upper(Str::random(8)),
            'status' => CompanyStatus::Active,
            'primary_contact_name' => fake()->name(),
            'primary_contact_email' => fake()->unique()->companyEmail(),
            'primary_contact_phone' => fake()->numerify('##########'),
            'notes' => null,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn () => ['status' => CompanyStatus::Archived]);
    }
}
