<?php

namespace Database\Factories\TaskManagement;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Models\EmployeeCapacity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeCapacity>
 */
class EmployeeCapacityFactory extends Factory
{
    /** @var class-string<EmployeeCapacity> */
    protected $model = EmployeeCapacity::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'weekly_hours' => 40,
            'working_days' => EmployeeCapacity::defaultWorkingDays(),
            'effective_from' => now()->startOfYear()->toDateString(),
        ];
    }
}
