<?php

namespace App\Modules\RecruiterOperations\Services\Assessments;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\AssessmentAssignmentStatus;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use App\Modules\RecruiterOperations\Services\RecruiterNotifier;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Gives an assessment's current published version to recruiters: one,
 * several or the whole recruiter team (everyone RecruiterDirectory lists).
 *
 * A recruiter holds at most one open assignment per assessment, and is never
 * given the same version twice. Anyone who already has it is skipped and
 * reported back rather than failing the whole batch. Assignments stay pinned
 * to the version they were given.
 */
class AssessmentAssignmentService
{
    public function __construct(
        protected RecruiterDirectory $directory,
        protected RecruiterNotifier $notifier,
    ) {}

    /**
     * @return list<int>
     */
    public function teamEmployeeIds(): array
    {
        return $this->directory->assignableRecruiters()->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array{created: Collection<int, AssessmentAssignment>, skipped: list<array{employee_id: int, name: string, reason: string}>}
     */
    public function assign(Assessment $assessment, array $employeeIds, ?CarbonInterface $dueAt, User $actor): array
    {
        $this->ensureInviter($actor);

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
                throw ValidationException::withMessages(['employee_ids' => 'Assessments can only be assigned to active recruiters.']);
            }
        }

        $result = DB::transaction(function () use ($assessment, $employeeIds, $employees, $dueAt, $actor) {
            /** @var Assessment $locked */
            $locked = Assessment::query()->whereKey($assessment->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isAssignable()) {
                throw ValidationException::withMessages(['assessment' => 'Only a published training quiz can be assigned.']);
            }

            $versionId = (int) $locked->current_version_id;
            $existing = AssessmentAssignment::query()
                ->forAssessment($locked)
                ->whereIn('employee_id', $employeeIds)
                ->get(['id', 'employee_id', 'assessment_version_id', 'status'])
                ->groupBy('employee_id');

            $created = collect();
            $skipped = [];

            foreach ($employeeIds as $employeeId) {
                $reason = $this->skipReason($existing->get($employeeId, collect()), $versionId);

                if ($reason !== null) {
                    $skipped[] = [
                        'employee_id' => $employeeId,
                        'name' => $employees->get($employeeId)?->user->name ?? 'Recruiter',
                        'reason' => $reason,
                    ];

                    continue;
                }

                $created->push($this->create($versionId, $employeeId, $dueAt, $actor, null));
            }

            return ['created' => $created, 'skipped' => $skipped];
        });

        foreach ($result['created'] as $assignment) {
            DB::afterCommit(fn () => $this->notifier->assessmentAssigned($assignment, $actor));
        }

        return $result;
    }

    /**
     * Gives the quizzes linked to a training version along with the course.
     * Runs inside the training assignment's transaction; the training
     * notification already tells the recruiter, so no second one is sent.
     *
     * @return int number of quiz assignments created
     */
    public function assignForTraining(TrainingAssignment $training, User $actor): int
    {
        $training->loadMissing('version.assessmentVersions');
        $created = 0;

        foreach ($training->version->assessmentVersions as $version) {
            $existing = AssessmentAssignment::query()
                ->where('employee_id', $training->employee_id)
                ->whereHas('version', fn ($query) => $query->where('assessment_id', $version->assessment_id))
                ->get(['id', 'employee_id', 'assessment_version_id', 'status']);

            if ($this->skipReason($existing, $version->id) !== null) {
                continue;
            }

            $this->create($version->id, $training->employee_id, $training->due_at, $actor, $training->id);
            $created++;
        }

        return $created;
    }

    /**
     * Withdraws an assignment that has no attempts. Anything attempted is
     * kept as history.
     */
    public function unassign(AssessmentAssignment $assignment, User $actor): void
    {
        $this->ensureInviter($actor);

        DB::transaction(function () use ($assignment) {
            /** @var AssessmentAssignment $locked */
            $locked = AssessmentAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            if ($locked->attempts()->exists()) {
                throw ValidationException::withMessages([
                    'assignment' => 'This assessment has already been attempted, so it is kept as history.',
                ]);
            }

            $locked->delete();
        });
    }

    /**
     * Removes the unattempted quiz assignments that came with a training
     * assignment being withdrawn.
     */
    public function removeForTraining(TrainingAssignment $training): void
    {
        AssessmentAssignment::query()
            ->where('training_assignment_id', $training->id)
            ->whereDoesntHave('attempts')
            ->get()
            ->each(fn (AssessmentAssignment $assignment) => $assignment->delete());
    }

    // Internals

    /**
     * @param  Collection<int, AssessmentAssignment>  $held
     */
    protected function skipReason(Collection $held, int $versionId): ?string
    {
        return match (true) {
            $held->contains(fn (AssessmentAssignment $a) => $a->assessment_version_id === $versionId) => 'Already has this version.',
            $held->contains(fn (AssessmentAssignment $a) => $a->status !== AssessmentAssignmentStatus::Completed) => 'Already has this assessment open.',
            default => null,
        };
    }

    protected function create(int $versionId, int $employeeId, ?CarbonInterface $dueAt, User $actor, ?int $trainingAssignmentId): AssessmentAssignment
    {
        $assignment = new AssessmentAssignment;
        $assignment->forceFill([
            'assessment_version_id' => $versionId,
            'employee_id' => $employeeId,
            'assigned_by_user_id' => $actor->id,
            'training_assignment_id' => $trainingAssignmentId,
            'due_at' => $dueAt,
            'status' => AssessmentAssignmentStatus::Assigned,
            'assigned_at' => now(),
        ])->save();

        return $assignment;
    }

    protected function ensureInviter(User $actor): void
    {
        if (! $actor->can(Ability::RecruiterAccess->value) || ! $actor->can(Ability::InviteRecruiterAssessments->value)) {
            throw new AuthorizationException('You cannot assign recruiter assessments.');
        }
    }

    /**
     * Guards the training link: only a published version of an assignable
     * training quiz can be attached.
     */
    public static function linkable(AssessmentVersion $version): bool
    {
        $version->loadMissing('assessment');

        return $version->isPublished()
            && $version->assessment->isAssignable()
            && $version->assessment->current_version_id === $version->id;
    }
}
