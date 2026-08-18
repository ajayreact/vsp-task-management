<?php

namespace Database\Factories\TaskManagement;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\AssignmentMode;
use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Enums\TaskType;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /** @var class-string<Task> */
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tm_project_id' => Project::factory(),
            'department_id' => null,
            'title' => ucfirst(fake()->words(4, true)),
            'description' => fake()->optional()->paragraph(),
            'type' => TaskType::Design,
            'priority' => TaskPriority::Normal,
            'status' => TaskStatus::Draft,
            'assignment_mode' => AssignmentMode::Direct,
            'assigned_employee_id' => null,
            'created_by_user_id' => User::factory(),
            'estimated_hours' => fake()->randomFloat(2, 1, 24),
            'due_at' => fake()->dateTimeBetween('now', '+1 month'),
        ];
    }

    /**
     * Unclaimed on the open board.
     */
    public function open(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Open,
            'assignment_mode' => AssignmentMode::Open,
            'assigned_employee_id' => null,
        ]);
    }

    /**
     * Handed to someone who has not yet responded.
     */
    public function awaitingAcceptance(Employee $employee): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Assigned,
            'assignment_mode' => AssignmentMode::Direct,
            'assigned_employee_id' => $employee->id,
        ]);
    }

    public function acceptedBy(Employee $employee): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::InProgress,
            'assigned_employee_id' => $employee->id,
            'started_at' => now(),
        ]);
    }
}
