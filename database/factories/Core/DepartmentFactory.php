<?php

namespace Database\Factories\Core;

use App\Modules\Core\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /** @var class-string<Department> */
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Creative', 'Performance Marketing', 'Content', 'Web Development',
            'Client Servicing', 'Media Buying', 'Strategy', 'Operations',
        ]).' '.fake()->unique()->numberBetween(1, 9999);

        return [
            'name' => $name,
            'code' => Str::upper(Str::random(6)),
            'description' => fake()->optional()->sentence(),
            'parent_id' => null,
            'head_employee_id' => null,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
