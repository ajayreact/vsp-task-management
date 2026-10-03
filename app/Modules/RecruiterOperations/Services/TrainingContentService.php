<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingContentStatus;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Enums\TrainingLessonContentType;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use App\Modules\RecruiterOperations\Models\TrainingCategory;
use App\Modules\RecruiterOperations\Models\TrainingCourse;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Services\Assessments\AssessmentAssignmentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Authoring for recruiter training: categories, courses, versions and
 * lessons. Enforces the versioning rules:
 *
 * - a course has at most one draft version, and only a draft can change;
 * - publishing freezes the draft, makes it the version new assignments get,
 *   and archives the version it replaces; it waits until the version's
 *   content reviews are finished (TrainingContentReviewService);
 * - assignments stay pinned to the version they were given, so nothing here
 *   ever edits or deletes a version that learners hold.
 */
class TrainingContentService
{
    public function __construct(protected TrainingContentReviewService $reviews) {}

    // Categories

    /**
     * @param  array{name: string, description?: string|null, level_number?: int|null, sort_order?: int|null}  $data
     */
    public function createCategory(array $data, User $actor): TrainingCategory
    {
        $this->ensureManager($actor);

        $category = new TrainingCategory;
        $category->fill($this->categoryAttributes($data));
        $category->forceFill([
            'slug' => $this->uniqueSlug(TrainingCategory::class, $data['name']),
            'is_active' => true,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ])->save();

        return $category;
    }

    /**
     * @param  array{name: string, description?: string|null, level_number?: int|null, sort_order?: int|null}  $data
     */
    public function updateCategory(TrainingCategory $category, array $data, User $actor): TrainingCategory
    {
        $this->ensureManager($actor);

        $category->fill($this->categoryAttributes($data));
        $category->updated_by_user_id = $actor->id;
        $category->save();

        return $category;
    }

    public function setCategoryActive(TrainingCategory $category, bool $active, User $actor): TrainingCategory
    {
        $this->ensureManager($actor);

        $category->forceFill(['is_active' => $active, 'updated_by_user_id' => $actor->id])->save();

        return $category;
    }

    // Courses

    /**
     * Creates the course with an empty draft Version 1.
     *
     * @param  array{category_id: int, title: string, description?: string|null, estimated_minutes?: int|null}  $data
     */
    public function createCourse(array $data, User $actor): TrainingCourse
    {
        $this->ensureManager($actor);
        $this->ensureActiveCategory((int) $data['category_id']);

        return DB::transaction(function () use ($data, $actor) {
            $course = new TrainingCourse;
            $course->fill([
                'category_id' => (int) $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]);
            $course->forceFill([
                'slug' => $this->uniqueSlug(TrainingCourse::class, $data['title']),
                'status' => TrainingContentStatus::Draft,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ])->save();

            $version = new TrainingCourseVersion;
            $version->fill(['estimated_minutes' => $data['estimated_minutes'] ?? null]);
            $version->forceFill([
                'course_id' => $course->id,
                'version_number' => 1,
                'status' => TrainingContentStatus::Draft,
                'created_by_user_id' => $actor->id,
            ])->save();

            return $course;
        });
    }

    /**
     * Course title, description and category. Lesson content is versioned and
     * never changed here.
     *
     * @param  array{category_id: int, title: string, description?: string|null}  $data
     */
    public function updateCourse(TrainingCourse $course, array $data, User $actor): TrainingCourse
    {
        $this->ensureManager($actor);

        if ((int) $data['category_id'] !== $course->category_id) {
            $this->ensureActiveCategory((int) $data['category_id']);
        }

        $course->fill([
            'category_id' => (int) $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
        ]);
        $course->updated_by_user_id = $actor->id;
        $course->save();

        return $course;
    }

    /**
     * Stops new assignments. Existing assignments stay learnable.
     */
    public function archiveCourse(TrainingCourse $course, User $actor): TrainingCourse
    {
        $this->ensureManager($actor);

        $course->forceFill(['status' => TrainingContentStatus::Archived, 'updated_by_user_id' => $actor->id])->save();

        return $course;
    }

    public function restoreCourse(TrainingCourse $course, User $actor): TrainingCourse
    {
        $this->ensureManager($actor);

        $course->forceFill([
            'status' => $course->current_version_id !== null ? TrainingContentStatus::Published : TrainingContentStatus::Draft,
            'updated_by_user_id' => $actor->id,
        ])->save();

        return $course;
    }

    // Versions

    /**
     * A new draft copied from an existing version (the latest by default),
     * lessons and files included.
     */
    public function createVersion(TrainingCourse $course, User $actor, ?TrainingCourseVersion $source = null): TrainingCourseVersion
    {
        $this->ensureManager($actor);

        if ($source !== null && $source->course_id !== $course->id) {
            throw ValidationException::withMessages(['source_version_id' => 'That version belongs to another course.']);
        }

        return DB::transaction(function () use ($course, $actor, $source) {
            TrainingCourse::query()->whereKey($course->id)->lockForUpdate()->first();

            if ($course->versions()->where('status', TrainingContentStatus::Draft->value)->exists()) {
                throw ValidationException::withMessages([
                    'version' => 'This course already has a draft version. Edit or publish it first.',
                ]);
            }

            $source ??= $course->versions()->orderByDesc('version_number')->first();
            $number = ((int) $course->versions()->max('version_number')) + 1;

            $version = new TrainingCourseVersion;
            $version->fill([
                'description' => $source?->description,
                'estimated_minutes' => $source?->estimated_minutes,
            ]);
            $version->forceFill([
                'course_id' => $course->id,
                'version_number' => $number,
                'status' => TrainingContentStatus::Draft,
                'created_by_user_id' => $actor->id,
            ])->save();

            foreach ($source === null ? [] : $source->lessons as $lesson) {
                $this->cloneLesson($lesson, $version, $actor);
            }

            foreach ($source === null ? [] : $source->assessmentVersions as $assessmentVersion) {
                $version->assessmentVersions()->attach($assessmentVersion->id, [
                    'sort_order' => (int) $assessmentVersion->getRelationValue('pivot')?->getAttribute('sort_order'),
                ]);
            }

            return $version;
        });
    }

    /**
     * @param  array{description?: string|null, estimated_minutes?: int|null}  $data
     */
    public function updateVersion(TrainingCourseVersion $version, array $data, User $actor): TrainingCourseVersion
    {
        $this->ensureManager($actor);
        $this->ensureDraft($version);

        $version->fill([
            'description' => $data['description'] ?? null,
            'estimated_minutes' => $data['estimated_minutes'] ?? null,
        ])->save();

        return $version;
    }

    public function publishVersion(TrainingCourseVersion $version, User $actor): TrainingCourseVersion
    {
        $this->ensureManager($actor);

        return DB::transaction(function () use ($version, $actor) {
            /** @var TrainingCourse $course */
            $course = TrainingCourse::query()->whereKey($version->course_id)->lockForUpdate()->firstOrFail();
            $version->refresh();

            $this->ensureDraft($version);

            if ($course->isArchived()) {
                throw ValidationException::withMessages(['version' => 'Restore the course before publishing a version.']);
            }

            if (! $version->lessons()->exists()) {
                throw ValidationException::withMessages(['version' => 'Add at least one lesson before publishing.']);
            }

            $this->reviews->ensureReadyToPublish($version);

            $course->versions()
                ->whereKeyNot($version->id)
                ->where('status', TrainingContentStatus::Published->value)
                ->get()
                ->each(fn (TrainingCourseVersion $previous) => $previous->forceFill(['status' => TrainingContentStatus::Archived])->save());

            $version->forceFill([
                'status' => TrainingContentStatus::Published,
                'published_at' => now(),
            ])->save();

            $course->forceFill([
                'status' => TrainingContentStatus::Published,
                'current_version_id' => $version->id,
                'updated_by_user_id' => $actor->id,
            ])->save();

            return $version;
        });
    }

    /**
     * Retires a published version. People already on it keep it. When it was
     * the live version, the course stops taking new assignments until another
     * version is published.
     */
    public function archiveVersion(TrainingCourseVersion $version, User $actor): TrainingCourseVersion
    {
        $this->ensureManager($actor);

        return DB::transaction(function () use ($version, $actor) {
            /** @var TrainingCourse $course */
            $course = TrainingCourse::query()->whereKey($version->course_id)->lockForUpdate()->firstOrFail();
            $version->refresh();

            if (! $version->isPublished()) {
                throw ValidationException::withMessages(['version' => 'Only a published version can be archived.']);
            }

            $version->forceFill(['status' => TrainingContentStatus::Archived])->save();

            if ($course->current_version_id === $version->id) {
                $fallback = $course->versions()
                    ->where('status', TrainingContentStatus::Published->value)
                    ->orderByDesc('version_number')
                    ->first();

                $course->forceFill([
                    'current_version_id' => $fallback?->id,
                    'status' => $course->isArchived()
                        ? TrainingContentStatus::Archived
                        : ($fallback !== null ? TrainingContentStatus::Published : TrainingContentStatus::Draft),
                    'updated_by_user_id' => $actor->id,
                ])->save();
            }

            return $version;
        });
    }

    /**
     * Throws away an unpublished draft. The only version of a course cannot be
     * discarded.
     */
    public function discardDraft(TrainingCourseVersion $version, User $actor): void
    {
        $this->ensureManager($actor);
        $this->ensureDraft($version);

        if (! TrainingCourseVersion::query()->where('course_id', $version->course_id)->whereKeyNot($version->id)->exists()) {
            throw ValidationException::withMessages(['version' => 'This is the course\'s only version, so it cannot be discarded.']);
        }

        DB::transaction(function () use ($version) {
            foreach ($version->lessons as $lesson) {
                $this->removeLessonFiles($lesson);
                $lesson->delete();
            }

            $version->delete();
        });
    }

    // Linked quizzes

    /**
     * Links a quiz to a draft course version. Only the current published
     * version of a training quiz can be linked; recruiters given this course
     * version later also get that quiz version.
     */
    public function attachAssessment(TrainingCourseVersion $version, AssessmentVersion $assessmentVersion, User $actor): void
    {
        $this->ensureManager($actor);
        $this->ensureDraft($version);

        if (! AssessmentAssignmentService::linkable($assessmentVersion)) {
            throw ValidationException::withMessages(['assessment_version_id' => 'Choose the current published version of a training quiz.']);
        }

        if ($version->assessmentVersions()->where('ro_assessment_versions.assessment_id', $assessmentVersion->assessment_id)->exists()) {
            throw ValidationException::withMessages(['assessment_version_id' => 'This quiz is already linked to this course version.']);
        }

        $version->assessmentVersions()->attach($assessmentVersion->id, [
            'sort_order' => ((int) $version->assessmentVersions()->max('ro_training_version_assessments.sort_order')) + 1,
        ]);
    }

    public function detachAssessment(TrainingCourseVersion $version, AssessmentVersion $assessmentVersion, User $actor): void
    {
        $this->ensureManager($actor);
        $this->ensureDraft($version);

        $version->assessmentVersions()->detach($assessmentVersion->id);
    }

    // Lessons

    /**
     * @param  array{title: string, description?: string|null, content_type: string, body?: string|null, duration_minutes?: int|null, is_required?: bool, external_url?: string|null}  $data
     */
    public function createLesson(TrainingCourseVersion $version, array $data, ?UploadedFile $file, User $actor): TrainingLesson
    {
        $this->ensureManager($actor);
        $this->ensureDraft($version);

        $type = TrainingLessonContentType::from($data['content_type']);

        if ($type->usesFile() && $file === null) {
            throw ValidationException::withMessages(['file' => 'Upload the '.strtolower($type->label()).' for this lesson.']);
        }

        return DB::transaction(function () use ($version, $data, $file, $actor, $type) {
            $lesson = new TrainingLesson;
            $lesson->fill($this->lessonAttributes($data, $type));
            $lesson->forceFill([
                'course_version_id' => $version->id,
                'slug' => $this->uniqueLessonSlug($version, $data['title']),
                'sort_order' => ((int) $version->lessons()->max('sort_order')) + 1,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ])->save();

            if ($type->usesFile() && $file !== null) {
                $this->attachFile($lesson, $file, $actor);
            }

            return $lesson;
        });
    }

    /**
     * @param  array{title: string, description?: string|null, content_type: string, body?: string|null, duration_minutes?: int|null, is_required?: bool, external_url?: string|null}  $data
     */
    public function updateLesson(TrainingLesson $lesson, array $data, ?UploadedFile $file, User $actor): TrainingLesson
    {
        $this->ensureManager($actor);
        $this->ensureDraft($lesson->version);

        $type = TrainingLessonContentType::from($data['content_type']);

        if ($type->usesFile() && $file === null) {
            $existing = $lesson->file();

            if ($existing === null || ! in_array($existing->mime_type, $type->allowedMimeTypes(), true)) {
                throw ValidationException::withMessages(['file' => 'Upload the '.strtolower($type->label()).' for this lesson.']);
            }
        }

        return DB::transaction(function () use ($lesson, $data, $file, $actor, $type) {
            $attributes = $this->lessonAttributes($data, $type);

            // With structured English content the body mirrors it and is
            // edited on the content page, not here.
            if ($lesson->contents()->where('locale', TrainingLanguage::English->value)->exists()) {
                unset($attributes['body']);
            }

            $lesson->fill($attributes);
            $lesson->updated_by_user_id = $actor->id;
            $lesson->save();

            if (! $type->usesFile()) {
                $lesson->clearMediaCollection(TrainingLesson::FILE_COLLECTION);
            } elseif ($file !== null) {
                $this->attachFile($lesson, $file, $actor);
            }

            return $lesson;
        });
    }

    public function deleteLesson(TrainingLesson $lesson, User $actor): void
    {
        $this->ensureManager($actor);
        $this->ensureDraft($lesson->version);

        DB::transaction(function () use ($lesson) {
            $version = $lesson->version;
            $this->removeLessonFiles($lesson);
            $lesson->delete();
            $this->resequence($version);
        });
    }

    /**
     * Moves a draft lesson one place up or down.
     */
    public function moveLesson(TrainingLesson $lesson, string $direction, User $actor): void
    {
        $this->ensureManager($actor);
        $this->ensureDraft($lesson->version);

        DB::transaction(function () use ($lesson, $direction) {
            $version = $lesson->version;
            $this->resequence($version);

            $ordered = $version->lessons()->get()->values();
            $index = $ordered->search(fn (TrainingLesson $item) => $item->id === $lesson->id);

            if (! is_int($index)) {
                return;
            }

            $target = $direction === 'up' ? $index - 1 : $index + 1;

            if ($target < 0 || $target >= $ordered->count()) {
                return;
            }

            $current = $ordered[$index];
            $neighbour = $ordered[$target];
            [$currentOrder, $neighbourOrder] = [$current->sort_order, $neighbour->sort_order];

            $current->forceFill(['sort_order' => $neighbourOrder])->saveQuietly();
            $neighbour->forceFill(['sort_order' => $currentOrder])->saveQuietly();
        });
    }

    // Internals

    protected function ensureManager(User $actor): void
    {
        if (! $actor->can(Ability::RecruiterAccess->value) || ! $actor->can(Ability::ManageRecruiterTraining->value)) {
            throw new AuthorizationException('You cannot manage recruiter training.');
        }
    }

    protected function ensureDraft(TrainingCourseVersion $version): void
    {
        if (! $version->isDraft()) {
            throw ValidationException::withMessages([
                'version' => 'Published and archived versions cannot be changed. Create a new version instead.',
            ]);
        }
    }

    protected function ensureActiveCategory(int $categoryId): void
    {
        if (! TrainingCategory::query()->whereKey($categoryId)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['category_id' => 'Choose an active category.']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function categoryAttributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'level_number' => $data['level_number'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function lessonAttributes(array $data, TrainingLessonContentType $type): array
    {
        if ($type === TrainingLessonContentType::ExternalResource && blank($data['external_url'] ?? null)) {
            throw ValidationException::withMessages(['external_url' => 'Add the link to the external resource.']);
        }

        return [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'content_type' => $type,
            'body' => $data['body'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'is_required' => (bool) ($data['is_required'] ?? true),
            'external_url' => $data['external_url'] ?? null,
        ];
    }

    protected function attachFile(TrainingLesson $lesson, UploadedFile $file, User $actor): void
    {
        $lesson->addMedia($file)
            ->withCustomProperties(['uploaded_by_user_id' => $actor->id])
            ->toMediaCollection(TrainingLesson::FILE_COLLECTION);
    }

    protected function cloneLesson(TrainingLesson $source, TrainingCourseVersion $version, User $actor): TrainingLesson
    {
        $copy = $source->replicate(['course_version_id', 'created_by_user_id', 'updated_by_user_id']);
        $copy->forceFill([
            'course_version_id' => $version->id,
            'created_by_user_id' => $actor->id,
            'updated_by_user_id' => $actor->id,
        ])->save();

        $file = $source->file();

        if ($file !== null) {
            $file->copy($copy, TrainingLesson::FILE_COLLECTION, (string) config('recruiter-training.media.disk', 'local'));
        }

        foreach ($source->contents()->get() as $content) {
            $content->replicate(['lesson_id'])->forceFill(['lesson_id' => $copy->id])->save();
        }

        return $copy;
    }

    protected function removeLessonFiles(TrainingLesson $lesson): void
    {
        $lesson->clearMediaCollection(TrainingLesson::FILE_COLLECTION);
        Storage::disk((string) config('recruiter-training.media.disk', 'local'))
            ->deleteDirectory("recruiter-training/audio/{$lesson->id}");
    }

    protected function resequence(TrainingCourseVersion $version): void
    {
        $position = 1;

        foreach ($version->lessons()->get() as $lesson) {
            if ($lesson->sort_order !== $position) {
                $lesson->forceFill(['sort_order' => $position])->saveQuietly();
            }

            $position++;
        }
    }

    /**
     * @param  class-string<TrainingCategory|TrainingCourse>  $model
     */
    protected function uniqueSlug(string $model, string $source): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($model::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    protected function uniqueLessonSlug(TrainingCourseVersion $version, string $title): string
    {
        $base = Str::slug($title) ?: 'lesson';
        $slug = $base;
        $suffix = 2;

        while (TrainingLesson::query()->where('course_version_id', $version->id)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
