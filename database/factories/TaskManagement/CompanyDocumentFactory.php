<?php

namespace Database\Factories\TaskManagement;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\CompanyDocumentCategory;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyDocument>
 */
class CompanyDocumentFactory extends Factory
{
    /** @var class-string<CompanyDocument> */
    protected $model = CompanyDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tm_company_id' => Company::factory(),
            'title' => ucfirst(fake()->words(3, true)),
            'category' => fake()->randomElement(CompanyDocumentCategory::cases()),
            'description' => fake()->optional()->sentence(),
            'created_by_user_id' => User::factory(),
        ];
    }
}
