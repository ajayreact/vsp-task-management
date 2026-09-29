<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * A recruiter's movement through their own assignment. The only writer of
 * assignment status and lesson completion rows. Nothing here trusts a
 * completion state from the client: completion is recorded only by the
 * explicit completeLesson() action, and audio progress never completes
 * anything.
 *
 * Ownership is checked against the actor's own employee record, so even Super
 * Admin (who passes every policy) cannot learn on someone else's behalf.
 */
class TrainingProgressService
{
    /**
     * Longest single time-spent report accepted; a heartbeat covers far less.
     */
    public const MAX_SPENT_SECONDS_PER_REPORT = 300;

    public const MAX_AUDIO_SECONDS = 86400;

    /**
     * The assignment a person learns a course through: the open one, else the
     * most recently completed one.
     */
    public function learnerAssignment(TrainingCourse $course, ?Employee $employee): ?TrainingAssignment
    {
        if ($employee === null) {
            return null;
        }

        return TrainingAssignment::query()
            ->forEmployee($employee)
            ->forCourse($course)
            ->orderByRaw('case when status = ? then 1 else 0 end', [TrainingAssignmentStatus::Completed->value])
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{total: int, counted: int, completed: int, percent: int, completed_lesson_ids: list<int>}
     */
    public function progressFor(TrainingAssignment $assignment): array
    {
        $assignment->loadMissing(['version.lessons', 'completions']);

        $counted = TrainingProgressCalculator::countedLessonIds($assignment->version->lessons);
        $completed = $this->completedLessonIds($assignment);

        return [
            'total' => $assignment->version->lessons->count(),
            'counted' => count($counted),
            'completed' => count(array_intersect($counted, $completed)),
            'percent' => TrainingProgressCalculator::percent($counted, $completed),
            'completed_lesson_ids' => $completed,
        ];
    }

    /**
     * Opening a lesson records that the recruiter started it, and starts the
     * assignment if this is the first lesson opened.
     */
    public function startLesson(TrainingAssignment $assignment, TrainingLesson $lesson, User $actor): TrainingLessonCompletion
    {
        $this->ensureOwnLesson($assignment, $lesson, $actor);

        return DB::transaction(function () use ($assignment, $lesson) {
            $locked = $this->lock($assignment);
            $completion = $this->completionRow($locked, $lesson);

            $this->markStarted($locked);

            return $completion;
        });
    }

    /**
     * The explicit "Mark Lesson Complete" action. Completes the assignment when
     * every counted lesson is done. Repeating it changes nothing.
     */
    public function completeLesson(TrainingAssignment $assignment, TrainingLesson $lesson, User $actor): TrainingLessonCompletion
    {
        $this->ensureOwnLesson($assignment, $lesson, $actor);

        return DB::transaction(function () use ($assignment, $lesson) {
            $locked = $this->lock($assignment);
            $completion = $this->completionRow($locked, $lesson);

            if ($completion->completed_at === null) {
                $completion->forceFill(['completed_at' => now()])->save();
            }

            $this->markStarted($locked);
            $this->refreshStatus($locked);

            return $completion;
        });
    }

    /**
     * Where the recruiter is in the lesson audio, and how long they spent on the
     * page since the last report. Never marks the lesson or the course complete.
     */
    public function recordProgress(
        TrainingAssignment $assignment,
        TrainingLesson $lesson,
        User $actor,
        ?int $audioSeconds,
        ?int $spentSeconds,
    ): TrainingLessonCompletion {
        $this->ensureOwnLesson($assignment, $lesson, $actor);

        return DB::transaction(function () use ($assignment, $lesson, $audioSeconds, $spentSeconds) {
            $locked = $this->lock($assignment);
            $completion = $this->completionRow($locked, $lesson);

            if ($audioSeconds !== null) {
                $completion->audio_progress_seconds = max(0, min($audioSeconds, self::MAX_AUDIO_SECONDS));
            }

            if ($spentSeconds !== null && $spentSeconds > 0) {
                $completion->time_spent_seconds = (int) $completion->time_spent_seconds
                    + min($spentSeconds, self::MAX_SPENT_SECONDS_PER_REPORT);
            }

            $completion->save();
            $this->markStarted($locked);

            return $completion;
        });
    }

    /**
     * Where to pick up: the first counted lesson not yet completed, else the
     * first lesson not completed, else the first lesson.
     */
    public function resumeLesson(TrainingAssignment $assignment): ?TrainingLesson
    {
        $assignment->loadMissing(['version.lessons', 'completions']);

        $lessons = $assignment->version->lessons;
        $completed = $this->completedLessonIds($assignment);
        $counted = TrainingProgressCalculator::countedLessonIds($lessons);

        return $lessons->first(fn (TrainingLesson $lesson) => in_array($lesson->id, $counted, true) && ! in_array($lesson->id, $completed, true))
            ?? $lessons->first(fn (TrainingLesson $lesson) => ! in_array($lesson->id, $completed, true))
            ?? $lessons->first();
    }

    /**
     * @return list<int>
     */
    public function completedLessonIds(TrainingAssignment $assignment): array
    {
        return $assignment->completions
            ->filter(fn (TrainingLessonCompletion $completion) => $completion->completed_at !== null)
            ->pluck('lesson_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    protected function ensureOwnLesson(TrainingAssignment $assignment, TrainingLesson $lesson, User $actor): void
    {
        $employee = Employee::query()->where('user_id', $actor->id)->first();

        if (! $assignment->isOwnedBy($employee)) {
            throw new AuthorizationException('This training is assigned to someone else.');
        }

        if ($lesson->course_version_id !== $assignment->course_version_id) {
            throw (new ModelNotFoundException)->setModel(TrainingLesson::class, [$lesson->id]);
        }
    }

    protected function lock(TrainingAssignment $assignment): TrainingAssignment
    {
        /** @var TrainingAssignment */
        return TrainingAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();
    }

    protected function completionRow(TrainingAssignment $assignment, TrainingLesson $lesson): TrainingLessonCompletion
    {
        $completion = TrainingLessonCompletion::query()
            ->where('assignment_id', $assignment->id)
            ->where('lesson_id', $lesson->id)
            ->first();

        if ($completion === null) {
            $completion = new TrainingLessonCompletion;
            $completion->forceFill([
                'assignment_id' => $assignment->id,
                'lesson_id' => $lesson->id,
                'started_at' => now(),
            ])->save();
        } elseif ($completion->started_at === null) {
            $completion->forceFill(['started_at' => now()])->save();
        }

        return $completion;
    }

    protected function markStarted(TrainingAssignment $assignment): void
    {
        if ($assignment->started_at === null) {
            $assignment->started_at = now();
        }

        if ($assignment->status === TrainingAssignmentStatus::Assigned) {
            $assignment->status = TrainingAssignmentStatus::InProgress;
        }

        if ($assignment->isDirty()) {
            $assignment->save();
        }
    }

    protected function refreshStatus(TrainingAssignment $assignment): void
    {
        if ($assignment->isCompleted()) {
            return;
        }

        $assignment->load(['version.lessons', 'completions']);

        $counted = TrainingProgressCalculator::countedLessonIds($assignment->version->lessons);

        if (TrainingProgressCalculator::isComplete($counted, $this->completedLessonIds($assignment))) {
            $assignment->forceFill([
                'status' => TrainingAssignmentStatus::Completed,
                'completed_at' => now(),
            ])->save();
        }
    }
}
