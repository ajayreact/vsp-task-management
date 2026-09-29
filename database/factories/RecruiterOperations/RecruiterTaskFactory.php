<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskPriority;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskStatus;
use App\Modules\RecruiterOperations\Enums\RecruiterWorkType;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds rows directly for test setup. Application code creates and moves
 * recruiter tasks through RecruiterTaskService and RecruiterTaskWorkflow.
 *
 * @extends Factory<RecruiterTask>
 */
class RecruiterTaskFactory extends Factory
{
    /** @var class-string<RecruiterTask> */
    protected $model = RecruiterTask::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'Source '.fake()->numberBetween(5, 40).' STEM OPT candidates',
            'description' => fake()->optional()->sentence(),
            'instructions' => null,
            'work_type' => RecruiterWorkType::CandidateSourcing,
            'priority' => RecruiterTaskPriority::Normal,
            'status' => RecruiterTaskStatus::Assigned,
            'assigned_employee_id' => Employee::factory(),
            'created_by_user_id' => User::factory(),
            'due_at' => now()->addDay(),
            'target_count' => null,
        ];
    }

    public function assignedTo(Employee $employee): static
    {
        return $this->state(fn () => ['assigned_employee_id' => $employee->id]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn () => ['created_by_user_id' => $user->id]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => RecruiterTaskStatus::InProgress,
            'accepted_at' => now(),
            'started_at' => now(),
        ]);
    }

    public function onHold(): static
    {
        return $this->state(fn () => [
            'status' => RecruiterTaskStatus::OnHold,
            'accepted_at' => now(),
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => RecruiterTaskStatus::Completed,
            'accepted_at' => now(),
            'started_at' => now(),
            'completed_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn () => ['status' => RecruiterTaskStatus::Declined]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => [
            'status' => RecruiterTaskStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
