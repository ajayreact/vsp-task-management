<?php

namespace App\Modules\RecruiterOperations\Services\Assessments;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\AssessmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentType;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Assessments and their versions. The versioning rules match Training:
 *
 * - an assessment has at most one draft version, and only a draft changes;
 * - publishing freezes the draft, makes it the version new assignments get,
 *   and archives the version it replaces;
 * - duplicating a version creates a new draft with copies of its questions;
 * - assignments and attempts stay pinned to their version, so nothing here
 *   edits or deletes a version recruiters hold.
 *
 * Only training quizzes can be created in this phase.
 *
 * @phpstan-type VersionSettings array{instructions?: string|null, passing_percentage?: int|null, time_limit_minutes?: int|null, max_attempts?: int|null, randomize_questions?: bool|null, randomize_options?: bool|null, show_result?: bool|null, allow_review?: bool|null}
 */
class AssessmentContentService
{
    public function __construct(protected AssessmentQuestionService $questions) {}

    /**
     * Creates the assessment with an empty draft Version 1.
     *
     * @param  array<string, mixed>  $data  title, description, type and the VersionSettings keys
     */
    public function createAssessment(array $data, User $actor): Assessment
    {
        $this->questions->ensureManager($actor);

        $type = AssessmentType::tryFrom((string) ($data['type'] ?? AssessmentType::TrainingQuiz->value));

        if ($type === null || ! $type->isAvailable()) {
            throw ValidationException::withMessages(['type' => 'Only training quizzes can be created at the moment.']);
        }

        return DB::transaction(function () use ($data, $actor, $type) {
            $title = trim((string) $data['title']);
            $assessment = new Assessment;
            $assessment->fill(['title' => $title, 'description' => $data['description'] ?? null]);
            $assessment->forceFill([
                'type' => $type,
                'slug' => $this->uniqueSlug($title),
                'status' => AssessmentStatus::Draft,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ])->save();

            $version = new AssessmentVersion;
            $version->fill($this->settings($data));
            $version->forceFill([
                'assessment_id' => $assessment->id,
                'version_number' => 1,
                'status' => AssessmentStatus::Draft,
                'created_by_user_id' => $actor->id,
            ])->save();

            return $assessment;
        });
    }

    /**
     * @param  array{title: string, description?: string|null}  $data
     */
    public function updateAssessment(Assessment $assessment, array $data, User $actor): Assessment
    {
        $this->questions->ensureManager($actor);

        $assessment->fill(['title' => trim($data['title']), 'description' => $data['description'] ?? null]);
        $assessment->updated_by_user_id = $actor->id;
        $assessment->save();

        return $assessment;
    }

    /**
     * Stops new assignments. Existing assignments can still be taken.
     */
    public function archiveAssessment(Assessment $assessment, User $actor): Assessment
    {
        $this->questions->ensureManager($actor);

        $assessment->forceFill(['status' => AssessmentStatus::Archived, 'updated_by_user_id' => $actor->id])->save();

        return $assessment;
    }

    public function restoreAssessment(Assessment $assessment, User $actor): Assessment
    {
        $this->questions->ensureManager($actor);

        $assessment->forceFill([
            'status' => $assessment->current_version_id !== null ? AssessmentStatus::Published : AssessmentStatus::Draft,
            'updated_by_user_id' => $actor->id,
        ])->save();

        return $assessment;
    }

    /**
     * A new draft copied from an existing version (the latest by default),
     * settings and questions included.
     */
    public function createVersion(Assessment $assessment, User $actor, ?AssessmentVersion $source = null): AssessmentVersion
    {
        $this->questions->ensureManager($actor);

        if ($source !== null && $source->assessment_id !== $assessment->id) {
            throw ValidationException::withMessages(['source_version_id' => 'That version belongs to another assessment.']);
        }

        return DB::transaction(function () use ($assessment, $actor, $source) {
            Assessment::query()->whereKey($assessment->id)->lockForUpdate()->first();

            if ($assessment->versions()->where('status', AssessmentStatus::Draft->value)->exists()) {
                throw ValidationException::withMessages([
                    'version' => 'This assessment already has a draft version. Edit or publish it first.',
                ]);
            }

            $source ??= $assessment->versions()->orderByDesc('version_number')->first();

            $version = new AssessmentVersion;

            if ($source !== null) {
                $version->fill($source->only($version->getFillable()));
            }

            $version->forceFill([
                'assessment_id' => $assessment->id,
                'version_number' => ((int) $assessment->versions()->max('version_number')) + 1,
                'status' => AssessmentStatus::Draft,
                'published_at' => null,
                'created_by_user_id' => $actor->id,
            ])->save();

            foreach ($source === null ? [] : $source->questions()->with('options')->get() as $question) {
                $this->questions->copy($question, $version, $actor);
            }

            return $version;
        });
    }

    /**
     * @param  VersionSettings  $data
     */
    public function updateVersion(AssessmentVersion $version, array $data, User $actor): AssessmentVersion
    {
        $this->questions->ensureManager($actor);
        $this->ensureDraft($version);

        $version->fill($this->settings($data))->save();

        return $version;
    }

    public function publishVersion(AssessmentVersion $version, User $actor): AssessmentVersion
    {
        $this->questions->ensureManager($actor);

        return DB::transaction(function () use ($version, $actor) {
            /** @var Assessment $assessment */
            $assessment = Assessment::query()->whereKey($version->assessment_id)->lockForUpdate()->firstOrFail();
            $version->refresh();

            $this->ensureDraft($version);

            if ($assessment->isArchived()) {
                throw ValidationException::withMessages(['version' => 'Restore the assessment before publishing a version.']);
            }

            $questions = $version->questions()->with('options')->get();

            if ($questions->isEmpty()) {
                throw ValidationException::withMessages(['version' => 'Add at least one question before publishing.']);
            }

            foreach ($questions as $position => $question) {
                $problems = QuestionRules::problems($question->type, $question->options
                    ->map(fn ($option) => ['text' => $option->text, 'correct' => $option->is_correct])->values()->all());

                if ($problems !== []) {
                    throw ValidationException::withMessages([
                        'version' => 'Question '.($position + 1).' needs fixing before publishing: '.$problems[0],
                    ]);
                }
            }

            $assessment->versions()
                ->whereKeyNot($version->id)
                ->where('status', AssessmentStatus::Published->value)
                ->get()
                ->each(fn (AssessmentVersion $previous) => $previous->forceFill(['status' => AssessmentStatus::Archived])->save());

            $version->forceFill(['status' => AssessmentStatus::Published, 'published_at' => now()])->save();

            $assessment->forceFill([
                'status' => AssessmentStatus::Published,
                'current_version_id' => $version->id,
                'updated_by_user_id' => $actor->id,
            ])->save();

            return $version;
        });
    }

    /**
     * Retires a published version. Recruiters already on it keep it.
     */
    public function archiveVersion(AssessmentVersion $version, User $actor): AssessmentVersion
    {
        $this->questions->ensureManager($actor);

        return DB::transaction(function () use ($version, $actor) {
            /** @var Assessment $assessment */
            $assessment = Assessment::query()->whereKey($version->assessment_id)->lockForUpdate()->firstOrFail();
            $version->refresh();

            if (! $version->isPublished()) {
                throw ValidationException::withMessages(['version' => 'Only a published version can be archived.']);
            }

            $version->forceFill(['status' => AssessmentStatus::Archived])->save();

            if ($assessment->current_version_id === $version->id) {
                $fallback = $assessment->versions()
                    ->where('status', AssessmentStatus::Published->value)
                    ->orderByDesc('version_number')
                    ->first();

                $assessment->forceFill([
                    'current_version_id' => $fallback?->id,
                    'status' => $assessment->isArchived()
                        ? AssessmentStatus::Archived
                        : ($fallback !== null ? AssessmentStatus::Published : AssessmentStatus::Draft),
                    'updated_by_user_id' => $actor->id,
                ])->save();
            }

            return $version;
        });
    }

    /**
     * Throws away an unpublished draft. The only version cannot be discarded.
     */
    public function discardDraft(AssessmentVersion $version, User $actor): void
    {
        $this->questions->ensureManager($actor);
        $this->ensureDraft($version);

        if (! AssessmentVersion::query()->where('assessment_id', $version->assessment_id)->whereKeyNot($version->id)->exists()) {
            throw ValidationException::withMessages(['version' => 'This is the assessment\'s only version, so it cannot be discarded.']);
        }

        DB::transaction(function () use ($version) {
            $version->questions()->withTrashed()->get()->each(fn ($question) => $question->forceDelete());
            $version->delete();
        });
    }

    // Internals

    protected function ensureDraft(AssessmentVersion $version): void
    {
        if (! $version->isDraft()) {
            throw ValidationException::withMessages([
                'version' => 'Published and archived versions cannot be changed. Create a new version instead.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function settings(array $data): array
    {
        $passing = (int) ($data['passing_percentage'] ?? AssessmentVersion::DEFAULT_PASSING_PERCENTAGE);
        $attempts = (int) ($data['max_attempts'] ?? AssessmentVersion::DEFAULT_MAX_ATTEMPTS);
        $limit = blank($data['time_limit_minutes'] ?? null) ? null : (int) $data['time_limit_minutes'];

        if ($passing < 1 || $passing > 100) {
            throw ValidationException::withMessages(['passing_percentage' => 'The pass mark must be between 1 and 100%.']);
        }

        if ($attempts < 1 || $attempts > 10) {
            throw ValidationException::withMessages(['max_attempts' => 'Allow between 1 and 10 attempts.']);
        }

        if ($limit !== null && ($limit < 1 || $limit > 480)) {
            throw ValidationException::withMessages(['time_limit_minutes' => 'The time limit must be between 1 and 480 minutes.']);
        }

        return [
            'instructions' => blank($data['instructions'] ?? null) ? null : trim((string) $data['instructions']),
            'passing_percentage' => $passing,
            'time_limit_minutes' => $limit,
            'max_attempts' => $attempts,
            'randomize_questions' => (bool) ($data['randomize_questions'] ?? false),
            'randomize_options' => (bool) ($data['randomize_options'] ?? false),
            'show_result' => (bool) ($data['show_result'] ?? true),
            'allow_review' => (bool) ($data['allow_review'] ?? false),
        ];
    }

    protected function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'assessment';
        $slug = $base;
        $suffix = 2;

        while (Assessment::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
