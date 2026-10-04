<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingLessonCompletion;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Moves unfinished assignments from an older version of a course onto its live
 * published version. An explicit manager action, run from the console: normal
 * publishing still leaves existing assignments on the version they were given.
 *
 * Lesson progress is carried across by matching each lesson to the one with
 * the same slug (else title) in the live version, else to the live lesson
 * that has the old title as a section heading. Where several old lessons
 * were combined into one, progress carries over but the combined lesson has
 * to be finished again. Completed assignments are never moved, so finished
 * learning history stays on its own version.
 */
class TrainingAssignmentMover
{
    public const MOVED = 'Moved';

    public const ALREADY_ON_LIVE = 'Skipped: already has the live version';

    public const NO_LIVE_VERSION = 'Skipped: course has no published version';

    public function __construct(protected AssessmentAssignmentService $assessments) {}

    /**
     * @param  Collection<int, TrainingCourse>  $courses
     * @return list<array{course: string, recruiter: string, from: string, to: string, kept: int, dropped: int, reset: int, status: string}>
     */
    public function move(Collection $courses, ?User $actor, bool $dryRun): array
    {
        if (! $dryRun && ($actor === null || ! $actor->can('assign', TrainingCourse::class))) {
            throw new AuthorizationException('Moving assignments needs a user who can assign recruiter training.');
        }

        $report = [];

        foreach ($courses as $course) {
            array_push($report, ...$this->moveCourse($course, $actor, $dryRun));
        }

        return $report;
    }

    /**
     * @return list<array{course: string, recruiter: string, from: string, to: string, kept: int, dropped: int, reset: int, status: string}>
     */
    protected function moveCourse(TrainingCourse $course, ?User $actor, bool $dryRun): array
    {
        $live = $course->current_version_id !== null ? TrainingCourseVersion::query()->with('lessons.contents')->find($course->current_version_id) : null;

        $stale = TrainingAssignment::query()
            ->forCourse($course)
            ->where('status', '!=', TrainingAssignmentStatus::Completed->value)
            ->when($live !== null, fn ($query) => $query->where('course_version_id', '!=', $live->id))
            ->with(['version.lessons', 'completions', 'employee.user:id,name'])
            ->orderBy('id')
            ->get();

        $report = [];

        foreach ($stale as $assignment) {
            $row = [
                'course' => $course->title,
                'recruiter' => trim(($assignment->employee->user->name ?? 'Recruiter').' · '.$assignment->employee->employee_code),
                'from' => $assignment->version->label(),
                'to' => $live?->label() ?? '-',
                'kept' => 0,
                'dropped' => 0,
                'reset' => 0,
                'status' => self::MOVED,
            ];

            if ($live === null || ! $live->isPublished()) {
                $report[] = ['status' => self::NO_LIVE_VERSION] + $row;

                continue;
            }

            if (TrainingAssignment::query()->where('course_version_id', $live->id)->where('employee_id', $assignment->employee_id)->exists()) {
                $report[] = ['status' => self::ALREADY_ON_LIVE] + $row;

                continue;
            }

            $plan = $this->plan($assignment, $live);
            $row['kept'] = count($plan['keep']);
            $row['dropped'] = count($plan['drop']);
            $row['reset'] = count($plan['reset']);

            if (! $dryRun && $actor !== null) {
                $this->apply($assignment, $live, $plan, $actor);
            }

            $report[] = $row;
        }

        return $report;
    }

    /**
     * Which completion rows follow the recruiter to the live version, and the
     * lesson each one lands on. Where two old rows would land on the same
     * lesson, the one furthest along is kept. Rows landing on a lesson that
     * combines several old lessons keep their time but are no longer
     * complete.
     *
     * @return array{keep: array<int, int>, drop: list<int>, reset: list<int>}
     */
    protected function plan(TrainingAssignment $assignment, TrainingCourseVersion $live): array
    {
        $oldLessons = $assignment->version->lessons->keyBy('id');
        $keep = [];
        $drop = [];
        $reset = [];

        $combined = $oldLessons
            ->map(fn (TrainingLesson $old) => $this->match($old, $live->lessons)?->id)
            ->filter()
            ->countBy()
            ->filter(fn (int $count) => $count > 1)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->all();

        $ordered = $assignment->completions->sortByDesc(fn (TrainingLessonCompletion $completion) => [
            $completion->completed_at !== null ? 1 : 0,
            (int) $completion->audio_progress_seconds + (int) $completion->time_spent_seconds,
        ]);

        foreach ($ordered as $completion) {
            $old = $oldLessons->get($completion->lesson_id);
            $target = $old !== null ? $this->match($old, $live->lessons) : null;

            if ($target === null || in_array($target->id, $keep, true)) {
                $drop[] = $completion->id;

                continue;
            }

            $keep[$completion->id] = $target->id;

            if (in_array($target->id, $combined, true) && $completion->completed_at !== null) {
                $reset[] = $completion->id;
            }
        }

        return ['keep' => $keep, 'drop' => $drop, 'reset' => $reset];
    }

    /**
     * @param  Collection<int, TrainingLesson>  $lessons
     */
    protected function match(TrainingLesson $old, Collection $lessons): ?TrainingLesson
    {
        $title = fn (?string $value) => Str::lower(trim((string) preg_replace('/\s+/', ' ', (string) $value)));
        $wanted = $title($old->title);

        return $lessons->first(fn (TrainingLesson $lesson) => $lesson->slug === $old->slug)
            ?? $lessons->first(fn (TrainingLesson $lesson) => $title($lesson->title) === $wanted)
            ?? $lessons->first(fn (TrainingLesson $lesson) => collect($lesson->contentIn(TrainingLanguage::English)->sections ?? [])
                ->contains(fn (array $section) => $title($section['heading']) === $wanted));
    }

    /**
     * @param  array{keep: array<int, int>, drop: list<int>, reset: list<int>}  $plan
     */
    protected function apply(TrainingAssignment $assignment, TrainingCourseVersion $live, array $plan, User $actor): void
    {
        DB::transaction(function () use ($assignment, $live, $plan, $actor) {
            /** @var TrainingAssignment $locked */
            $locked = TrainingAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            if ($locked->isCompleted() || $locked->course_version_id === $live->id) {
                return;
            }

            TrainingLessonCompletion::query()->whereKey($plan['drop'])->delete();

            foreach ($plan['keep'] as $completionId => $lessonId) {
                TrainingLessonCompletion::query()->whereKey($completionId)->update(['lesson_id' => $lessonId]);
            }

            TrainingLessonCompletion::query()->whereKey($plan['reset'])->update(['completed_at' => null]);

            $this->assessments->removeForTraining($locked);
            $locked->forceFill(['course_version_id' => $live->id])->save();

            $locked->load(['version.lessons', 'completions']);
            $completed = $locked->completions
                ->filter(fn (TrainingLessonCompletion $completion) => $completion->completed_at !== null)
                ->pluck('lesson_id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            if (TrainingProgressCalculator::isComplete(TrainingProgressCalculator::countedLessonIds($locked->version->lessons), $completed)) {
                $locked->forceFill(['status' => TrainingAssignmentStatus::Completed, 'completed_at' => now()])->save();
            }

            $this->assessments->assignForTraining($locked, $actor);
        });
    }
}
