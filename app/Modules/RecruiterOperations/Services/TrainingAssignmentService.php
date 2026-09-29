<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentAssignmentService;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gives a course's current published version to recruiters, one, several or
 * the whole recruiter team (everyone RecruiterDirectory lists).
 *
 * A recruiter holds at most one open assignment per course, and is never given
 * the same version twice. Anyone who already has the course is skipped and
 * reported back rather than failing the whole batch.
 *
 * Quizzes linked to the course version are assigned along with it, and
 * withdrawn with it while they have no attempts.
 */
class TrainingAssignmentService
{
    public function __construct(
        protected RecruiterDirectory $directory,
        protected RecruiterNotifier $notifier,
        protected AssessmentAssignmentService $assessments,
    ) {}

    /**
     * Employee ids of the whole recruiter team.
     *
     * @return list<int>
     */
    public function teamEmployeeIds(): array
    {
        return $this->directory->assignableRecruiters()->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array{created: Collection<int, TrainingAssignment>, skipped: list<array{employee_id: int, name: string, reason: string}>}
     */
    public function assign(TrainingCourse $course, array $employeeIds, ?CarbonInterface $dueAt, User $actor): array
    {
        $this->ensureAssigner($actor);

        $employeeIds = array_values(array_unique(array_map('intval', $employeeIds)));

        if ($employeeIds === []) {
            throw ValidationException::withMessages(['employee_ids' => 'Choose at least one recruiter.']);
        }

        if ($dueAt !== null && $dueAt->isPast()) {
            throw ValidationException::withMessages(['due_at' => 'The due date cannot be in the past.']);
        }

        $employees = Employee::query()->with('user:id,name')->whereKey($employeeIds)->get()->keyBy('id');

        foreach ($employeeIds as $employeeId) {
            $employee = $employees->get($employeeId);

            if ($employee === null || ! $this->directory->isAssignable($employee)) {
                throw ValidationException::withMessages(['employee_ids' => 'Training can only be assigned to active recruiters.']);
            }
        }

        $result = DB::transaction(function () use ($course, $employeeIds, $employees, $dueAt, $actor) {
            /** @var TrainingCourse $locked */
            $locked = TrainingCourse::query()->whereKey($course->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isAssignable()) {
                throw ValidationException::withMessages(['course_id' => 'Only a published course can be assigned.']);
            }

            $versionId = (int) $locked->current_version_id;
            $existing = TrainingAssignment::query()
                ->forCourse($locked)
                ->whereIn('employee_id', $employeeIds)
                ->get(['id', 'employee_id', 'course_version_id', 'status'])
                ->groupBy('employee_id');

            $created = collect();
            $skipped = [];

            foreach ($employeeIds as $employeeId) {
                /** @var Collection<int, TrainingAssignment> $held */
                $held = $existing->get($employeeId, collect());
                $reason = match (true) {
                    $held->contains(fn (TrainingAssignment $a) => $a->status !== TrainingAssignmentStatus::Completed) => 'Already has this course open.',
                    $held->contains(fn (TrainingAssignment $a) => $a->course_version_id === $versionId) => 'Already completed this version.',
                    default => null,
                };

                if ($reason !== null) {
                    $skipped[] = [
                        'employee_id' => $employeeId,
                        'name' => $employees->get($employeeId)?->user->name ?? 'Recruiter',
                        'reason' => $reason,
                    ];

                    continue;
                }

                $assignment = new TrainingAssignment;
                $assignment->forceFill([
                    'course_version_id' => $versionId,
                    'employee_id' => $employeeId,
                    'assigned_by_user_id' => $actor->id,
                    'due_at' => $dueAt,
                    'status' => TrainingAssignmentStatus::Assigned,
                    'assigned_at' => now(),
                ])->save();

                $this->assessments->assignForTraining($assignment, $actor);
                $created->push($assignment);
            }

            return ['created' => $created, 'skipped' => $skipped];
        });

        foreach ($result['created'] as $assignment) {
            DB::afterCommit(fn () => $this->notifier->trainingAssigned($assignment, $actor));
        }

        return $result;
    }

    /**
     * Withdraws an assignment the recruiter has not started. Anything with
     * progress is kept as learning history.
     */
    public function unassign(TrainingAssignment $assignment, User $actor): void
    {
        $this->ensureAssigner($actor);

        DB::transaction(function () use ($assignment) {
            /** @var TrainingAssignment $locked */
            $locked = TrainingAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            if ($locked->isStarted() || $locked->completions()->exists()) {
                throw ValidationException::withMessages([
                    'assignment' => 'This training has already been started, so it is kept as learning history.',
                ]);
            }

            $this->assessments->removeForTraining($locked);
            $locked->delete();
        });
    }

    protected function ensureAssigner(User $actor): void
    {
        if (! $actor->can(Ability::RecruiterAccess->value) || ! $actor->can(Ability::AssignRecruiterTraining->value)) {
            throw new AuthorizationException('You cannot assign recruiter training.');
        }
    }
}
