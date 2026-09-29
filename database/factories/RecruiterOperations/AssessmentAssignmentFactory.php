<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\AssessmentAssignmentStatus;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds assignment rows for test setup. Application code assigns through
 * AssessmentAssignmentService.
 *
 * @extends Factory<AssessmentAssignment>
 */
class AssessmentAssignmentFactory extends Factory
{
    /** @var class-string<AssessmentAssignment> */
    protected $model = AssessmentAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assessment_version_id' => AssessmentVersion::factory()->published(),
            'employee_id' => Employee::factory(),
            'assigned_by_user_id' => null,
            'training_assignment_id' => null,
            'due_at' => null,
            'status' => AssessmentAssignmentStatus::Assigned,
            'result' => null,
            'assigned_at' => now(),
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function forEmployee(Employee $employee): static
    {
        return $this->state(fn () => ['employee_id' => $employee->id]);
    }

    public function forVersion(AssessmentVersion $version): static
    {
        return $this->state(fn () => ['assessment_version_id' => $version->id]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => ['due_at' => now()->subDay()->startOfMinute()]);
    }

    public function dueIn(int $days): static
    {
        return $this->state(fn () => ['due_at' => now()->addDays($days)->startOfMinute()]);
    }
}
