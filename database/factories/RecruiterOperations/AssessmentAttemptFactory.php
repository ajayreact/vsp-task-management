<?php

namespace Database\Factories\RecruiterOperations;

use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Enums\AttemptStatus;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Builds attempt rows for test setup (for example, to use up attempts).
 * Application code starts and scores attempts through AssessmentAttemptService.
 *
 * @extends Factory<AssessmentAttempt>
 */
class AssessmentAttemptFactory extends Factory
{
    /** @var class-string<AssessmentAttempt> */
    protected $model = AssessmentAttempt::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'assignment_id' => AssessmentAssignment::factory(),
            'assessment_version_id' => fn (array $attributes) => AssessmentAssignment::query()->find($attributes['assignment_id'])?->assessment_version_id,
            'employee_id' => fn (array $attributes) => AssessmentAssignment::query()->find($attributes['assignment_id'])?->employee_id,
            'attempt_number' => 1,
            'status' => AttemptStatus::Submitted,
            'active_assignment_id' => null,
            'layout' => ['questions' => [], 'options' => []],
            'started_at' => now()->subHour(),
            'expires_at' => null,
            'submitted_at' => now()->subMinutes(30),
            'auto_submitted' => false,
            'total_points' => 1,
            'awarded_points' => 0,
            'percentage' => '0.00',
            'result' => AssessmentResult::Failed,
            'scored_at' => now()->subMinutes(30),
        ];
    }

    public function forAssignment(AssessmentAssignment $assignment, int $number = 1): static
    {
        return $this->state(fn () => [
            'assignment_id' => $assignment->id,
            'assessment_version_id' => $assignment->assessment_version_id,
            'employee_id' => $assignment->employee_id,
            'attempt_number' => $number,
        ]);
    }
}
