<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingTrack;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Training tracks: the top of the training hierarchy. Courses not yet placed
 * in a track form their own "not in a track" group, so they stay reachable
 * without ever being shown under OPT or Bench Sales.
 */
class TrainingTrackCatalog
{
    public const UNASSIGNED_NAME = 'Not in a track';

    /**
     * @return Collection<int, TrainingTrack>
     */
    public function tracks(): Collection
    {
        return TrainingTrack::query()->active()->ordered()->get();
    }

    /**
     * The track for a route or form value. Null is the "not in a track"
     * group; an unknown or inactive slug is a 404.
     */
    public function resolve(string $slug): ?TrainingTrack
    {
        if ($slug === TrainingTrack::UNASSIGNED) {
            return null;
        }

        return TrainingTrack::query()->active()->where('slug', $slug)->first()
            ?? throw (new ModelNotFoundException)->setModel(TrainingTrack::class, [$slug]);
    }

    public function hasUnassignedCourses(): bool
    {
        return TrainingCourse::query()->whereNull('training_track_id')->exists();
    }

    /**
     * Track filter options. The "not in a track" group is offered only while
     * such courses exist.
     *
     * @return list<array{value: string, label: string}>
     */
    public function options(): array
    {
        $options = $this->tracks()
            ->map(fn (TrainingTrack $track) => ['value' => $track->slug, 'label' => $track->name])
            ->values()
            ->all();

        if ($this->hasUnassignedCourses()) {
            $options[] = ['value' => TrainingTrack::UNASSIGNED, 'label' => self::UNASSIGNED_NAME];
        }

        return $options;
    }

    /**
     * Track id => name, for course forms.
     *
     * @return list<array{id: int, label: string}>
     */
    public function formOptions(): array
    {
        return $this->tracks()
            ->map(fn (TrainingTrack $track) => ['id' => $track->id, 'label' => $track->name])
            ->values()
            ->all();
    }

    /**
     * Landing cards for people who manage training: every active track with
     * its course, module and lesson counts. Counts use each course's live
     * version (the draft when nothing is live yet), which is what managers
     * edit.
     *
     * @return list<array{slug: string, name: string, description: string|null, courses: int, modules: int, lessons: int}>
     */
    public function managerCards(): array
    {
        $courses = TrainingCourse::query()
            ->with('draftVersion:id,course_id')
            ->get(['id', 'training_track_id', 'current_version_id']);
        $versionIds = $courses->map(fn (TrainingCourse $course) => $course->current_version_id ?? $course->draftVersion?->id)->filter()->values();

        $lessonStats = TrainingLesson::query()
            ->whereIn('course_version_id', $versionIds)
            ->groupBy('course_version_id')
            ->get(['course_version_id', DB::raw('count(*) as lessons'), DB::raw("count(distinct coalesce(module, '')) as modules")])
            ->keyBy('course_version_id');

        $card = function (?TrainingTrack $track) use ($courses, $lessonStats): array {
            $inTrack = $courses->filter(fn (TrainingCourse $course) => $course->training_track_id === $track?->id);
            $stats = $inTrack->map(fn (TrainingCourse $course) => $lessonStats->get($course->current_version_id ?? $course->draftVersion?->id));

            return [
                'slug' => $track->slug ?? TrainingTrack::UNASSIGNED,
                'name' => $track->name ?? self::UNASSIGNED_NAME,
                'description' => $track?->description,
                'courses' => $inTrack->count(),
                'modules' => (int) $stats->sum(fn ($row) => (int) ($row->modules ?? 0)),
                'lessons' => (int) $stats->sum(fn ($row) => (int) ($row->lessons ?? 0)),
            ];
        };

        $cards = $this->tracks()->map(fn (TrainingTrack $track) => $card($track))->values()->all();
        $unassigned = $card(null);

        if ($unassigned['courses'] > 0) {
            $cards[] = $unassigned;
        }

        return $cards;
    }

    /**
     * Landing cards for a recruiter: only the tracks they have training in,
     * counted from their own assigned (pinned) versions.
     *
     * @param  Collection<int, TrainingAssignment>  $assignments  with version.course.track and version.lessons
     * @return list<array{slug: string, name: string, description: string|null, courses: int, lessons: int, completed: int}>
     */
    public function learnerCards(Collection $assignments): array
    {
        return $assignments
            ->groupBy(fn (TrainingAssignment $assignment) => $assignment->version->course->training_track_id ?? 0)
            ->map(function (Collection $group) {
                $track = $group->first()?->version->course->track;

                return [
                    'slug' => $track->slug ?? TrainingTrack::UNASSIGNED,
                    'name' => $track->name ?? self::UNASSIGNED_NAME,
                    'description' => $track?->description,
                    'courses' => $group->count(),
                    'lessons' => $group->sum(fn (TrainingAssignment $assignment) => $assignment->version->lessons->count()),
                    'completed' => $group->filter(fn (TrainingAssignment $assignment) => $assignment->isCompleted())->count(),
                    'sort' => $track === null ? PHP_INT_MAX : $track->sort_order,
                ];
            })
            ->sortBy('sort')
            ->map(fn (array $card) => array_diff_key($card, ['sort' => true]))
            ->values()
            ->all();
    }

    /**
     * Courses of a track for the track page: the live version's module and
     * lesson counts and duration. Never the lessons themselves.
     *
     * @return list<array{id: int, title: string, description: string|null, status: string, status_label: string, duration_minutes: int|null, modules: int, lessons: int}>
     */
    public function trackCourses(?TrainingTrack $track): array
    {
        $courses = TrainingCourse::query()
            ->inTrack($track)
            ->with([
                'category:id,level_number,sort_order',
                'currentVersion:id,estimated_minutes',
                'currentVersion.lessons:id,course_version_id,module,body,duration_minutes',
                'draftVersion:id,course_id,estimated_minutes',
                'draftVersion.lessons:id,course_version_id,module,body,duration_minutes',
            ])
            ->get();

        return $courses
            ->sortBy(fn (TrainingCourse $course) => [$course->category->sort_order ?? 0, $course->category->level_number ?? PHP_INT_MAX, $course->title])
            ->map(function (TrainingCourse $course) {
                /** @var TrainingCourseVersion|null $live */
                $live = $course->currentVersion ?? $course->draftVersion;
                $lessons = $live->lessons ?? collect();

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'description' => $course->description,
                    'status' => $course->status->value,
                    'status_label' => $course->status->label(),
                    'duration_minutes' => $live?->estimatedMinutes(),
                    'modules' => $lessons->map(fn (TrainingLesson $lesson) => (string) $lesson->module)->unique()->count(),
                    'lessons' => $lessons->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Once a course in a track has been assigned, its track is fixed: moving
     * it would show a recruiter's training under another track. A course not
     * yet in a track can still be placed in one.
     */
    public function isTrackLocked(TrainingCourse $course): bool
    {
        return $course->training_track_id !== null && TrainingAssignment::query()
            ->whereIn('course_version_id', $course->versions()->select('id'))
            ->exists();
    }

    /**
     * @throws ValidationException when the course is not in that track
     */
    public function ensureCourseInTrack(TrainingCourse $course, ?TrainingTrack $track, string $field = 'course_id'): void
    {
        if ($course->training_track_id !== $track?->id) {
            throw ValidationException::withMessages([
                $field => 'This course is not in the '.($track->name ?? strtolower(self::UNASSIGNED_NAME)).' training track.',
            ]);
        }
    }
}
