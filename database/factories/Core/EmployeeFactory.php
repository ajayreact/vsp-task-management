<?php

namespace Database\Factories\Core;

use App\Modules\Core\Enums\EmployeeStatus;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /** @var class-string<Employee> */
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'employee_code' => 'EMP-'.fake()->unique()->numberBetween(1000, 999999),
            'designation' => fake()->jobTitle(),
            'reporting_to_id' => null,
            'phone' => fake()->numerify('##########'),
            'joined_on' => fake()->dateTimeBetween('-4 years', '-1 month'),
            'exited_on' => null,
            'status' => EmployeeStatus::Active,
        ];
    }

    public function exited(): static
    {
        return $this->state(fn () => [
            'status' => EmployeeStatus::Exited,
            'exited_on' => now()->subWeek(),
        ]);
    }
}
