<?php

namespace App\Modules\RecruiterOperations\Services\Assessments;

use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\AssessmentAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Models\AssessmentAnswer;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Quiz counts for one recruiter's dashboard. Own results only: never a
 * ranking or comparison. Quizzes that hide results count in neither passed
 * nor failed.
 */
class AssessmentSummary
{
    /**
     * @return Collection<int, AssessmentAssignment>
     */
    public function assignmentsFor(Employee $employee): Collection
    {
        return AssessmentAssignment::query()
            ->forEmployee($employee)
            ->with(['version.assessment', 'attempts'])
            ->orderByRaw('case when status = ? then 1 else 0 end', [AssessmentAssignmentStatus::Completed->value])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->orderByDesc('assigned_at')
            ->get();
    }

    /**
     * @return array{assigned: int, open: int, overdue: int, pending_review: int, passed: int, failed: int}
     */
    public function countsFor(Employee $employee): array
    {
        return $this->counts($this->assignmentsFor($employee));
    }

    /**
     * @param  Collection<int, AssessmentAssignment>  $assignments
     * @return array{assigned: int, open: int, overdue: int, pending_review: int, passed: int, failed: int}
     */
    public function counts(Collection $assignments): array
    {
        $counts = ['assigned' => $assignments->count(), 'open' => 0, 'overdue' => 0, 'pending_review' => 0, 'passed' => 0, 'failed' => 0];

        foreach ($assignments as $assignment) {
            if ($assignment->result === AssessmentResult::PendingReview) {
                $counts['pending_review']++;
            } elseif ($assignment->isCompleted()) {
                if ($assignment->version->show_result) {
                    $assignment->result === AssessmentResult::Passed ? $counts['passed']++ : $counts['failed']++;
                }
            } elseif ($assignment->effectiveStatus() === AssessmentAssignmentStatus::Overdue) {
                $counts['overdue']++;
            } else {
                $counts['open']++;
            }
        }

        return $counts;
    }

    /**
     * Short answers waiting for a reviewer, across all recruiters.
     */
    public function awaitingReviewCount(): int
    {
        return AssessmentAnswer::query()->where('needs_review', true)->whereNull('reviewed_at')
            ->distinct()->count('attempt_id');
    }
}
