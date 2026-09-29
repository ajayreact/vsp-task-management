<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use Illuminate\Database\Eloquent\Collection;

/**
 * Training counts for one recruiter's dashboards. Learning progress only:
 * never a score or a ranking.
 */
class TrainingSummary
{
    public function __construct(protected TrainingProgressService $progress) {}

    /**
     * @return Collection<int, TrainingAssignment>
     */
    public function assignmentsFor(Employee $employee): Collection
    {
        return TrainingAssignment::query()
            ->forEmployee($employee)
            ->with(['version.course.category', 'version.lessons', 'completions'])
            ->orderByRaw('case when status = ? then 1 else 0 end', [TrainingAssignmentStatus::Completed->value])
            ->orderByRaw('due_at is null')
            ->orderBy('due_at')
            ->orderByDesc('assigned_at')
            ->get();
    }

    /**
     * @return array{assigned: int, not_started: int, in_progress: int, completed: int, overdue: int, overall_percent: int}
     */
    public function countsFor(Employee $employee): array
    {
        return $this->counts($this->assignmentsFor($employee));
    }

    /**
     * Overdue is counted on its own, so an overdue assignment is not also
     * counted as not started or in progress.
     *
     * @param  Collection<int, TrainingAssignment>  $assignments
     * @return array{assigned: int, not_started: int, in_progress: int, completed: int, overdue: int, overall_percent: int}
     */
    public function counts(Collection $assignments): array
    {
        $counts = [
            'assigned' => $assignments->count(),
            'not_started' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
            'overall_percent' => 0,
        ];
        $counted = 0;
        $done = 0;

        foreach ($assignments as $assignment) {
            match ($assignment->effectiveStatus()) {
                TrainingAssignmentStatus::Assigned => $counts['not_started']++,
                TrainingAssignmentStatus::InProgress => $counts['in_progress']++,
                TrainingAssignmentStatus::Completed => $counts['completed']++,
                TrainingAssignmentStatus::Overdue => $counts['overdue']++,
            };

            $progress = $this->progress->progressFor($assignment);
            $counted += $progress['counted'];
            $done += $progress['completed'];
        }

        $counts['overall_percent'] = $counted > 0 ? (int) floor($done * 100 / $counted) : 0;

        return $counts;
    }
}
