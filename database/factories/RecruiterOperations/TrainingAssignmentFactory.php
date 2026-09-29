<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds assignment rows for test setup. Application code assigns through
 * TrainingAssignmentService.
 *
 * @extends Factory<TrainingAssignment>
 */
class TrainingAssignmentFactory extends Factory
{
    /** @var class-string<TrainingAssignment> */
    protected $model = TrainingAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_version_id' => TrainingCourseVersion::factory()->published(),
            'employee_id' => Employee::factory(),
            'assigned_by_user_id' => null,
            'due_at' => null,
            'status' => TrainingAssignmentStatus::Assigned,
            'assigned_at' => now(),
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(fn () => ['employee_id' => $employee->id]);
    }

    public function forVersion(TrainingCourseVersion $version): static
    {
        return $this->state(fn () => ['course_version_id' => $version->id]);
    }

    public function inProgress(): static
    {
        return $this->state(fn () => [
            'status' => TrainingAssignmentStatus::InProgress,
            'started_at' => now()->subHour(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => TrainingAssignmentStatus::Completed,
            'started_at' => now()->subDay(),
            'completed_at' => now()->subHour(),
        ]);
    }

    public function dueIn(int $days): static
    {
        return $this->state(fn () => ['due_at' => now()->addDays($days)->startOfMinute()]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['due_at' => now()->subDay()->startOfMinute()]);
    }
}
