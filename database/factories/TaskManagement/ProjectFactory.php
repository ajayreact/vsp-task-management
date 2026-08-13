<?php

namespace Database\Factories\TaskManagement;

use App\Modules\TaskManagement\Enums\ProjectStatus;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /** @var class-string<Project> */
    protected $model = Project::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tm_company_id' => Company::factory(),
            'name' => Str::title(fake()->words(3, true)),
            'code' => Str::upper(Str::random(8)),
            'description' => fake()->optional()->sentence(),
            'status' => ProjectStatus::Active,
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'due_date' => fake()->dateTimeBetween('now', '+6 months'),
            'manager_employee_id' => null,
            'budget_hours' => fake()->randomFloat(2, 20, 400),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Completed]);
    }
}
