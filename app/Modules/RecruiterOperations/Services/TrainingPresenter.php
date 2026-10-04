<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\TrainingLanguage;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingLesson;
use App\Modules\RecruiterOperations\Models\TrainingTrack;
use Illuminate\Support\Str;

/**
 * Shapes training records for the pages. Private files and audio are only
 * ever referenced by their authorized routes, never by storage URLs.
 */
class TrainingPresenter
{
    public function __construct(
        protected TrainingProgressService $progress,
        protected TrainingSpeechService $speech,
        protected TrainingLessonContentService $contents,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function lesson(TrainingLesson $lesson): array
    {
        $file = $lesson->file();

        return [
            'id' => $lesson->id,
            'module' => $lesson->module,
            'title' => $lesson->title,
            'description' => $lesson->description,
            'body' => $lesson->body,
            'languages' => $this->languages($lesson),
            'content_type' => $lesson->content_type->value,
            'content_type_label' => $lesson->content_type->label(),
            'duration_minutes' => $lesson->duration_minutes,
            'is_required' => $lesson->is_required,
            'external_url' => $lesson->external_url,
            'sort_order' => $lesson->sort_order,
            'file' => $file !== null ? [
                'name' => $file->file_name,
                'mime' => $file->mime_type,
                'size' => $file->size,
                'url' => route('recruiter.training.lessons.file', $lesson),
            ] : null,
        ];
    }

    /**
     * The lesson in every language, English (the source) first. English is
     * always there when the lesson has written content; a translation is null
     * until one is written, and is flagged when its English has changed since.
     *
     * @return list<array{code: string, label: string, native_label: string, canonical: bool, available: bool, structured: bool, review_status: string|null, review_label: string|null, outdated: bool, sections: list<array{kind: string, heading: string, body: string}>|null}>
     */
    public function languages(TrainingLesson $lesson): array
    {
        $languages = [];

        foreach (TrainingLanguage::cases() as $language) {
            $stored = $lesson->contentIn($language);
            $sections = $this->contents->sectionsFor($lesson, $language);
            $outdated = $stored !== null && $this->contents->isOutdated($lesson, $stored);

            $languages[] = [
                'code' => $language->value,
                'label' => $language->label(),
                'native_label' => $language->nativeLabel(),
                'canonical' => $language->isCanonical(),
                // A published translation still awaiting approval is unavailable: learners see English.
                'available' => $sections !== null && $sections !== [],
                'structured' => $stored !== null,
                'review_status' => $stored?->review_status->value,
                'review_label' => $outdated ? TrainingContentReviewService::OUTDATED_LABEL : $stored?->review_status->labelFor($language),
                'outdated' => $outdated,
                'sections' => $sections,
            ];
        }

        return $languages;
    }

    /**
     * What the audio player needs. Voices are only those the configured
     * provider really offers, Indian English first; each lesson language has
     * its own voices and its own text.
     *
     * @return array{delivery: string, voices: list<array{key: string, label: string, locale: string, flag: string}>, voices_by_language: array<string, list<array{key: string, label: string, locale: string, flag: string}>>, has_text_by_language: array<string, bool>, speech_url: string, has_text: bool}
     */
    public function audio(TrainingLesson $lesson): array
    {
        return [...$this->audioSettings(), ...$this->lessonAudio($lesson)];
    }

    /**
     * The audio settings every lesson shares: delivery and voices.
     *
     * @return array{delivery: string, voices: list<array{key: string, label: string, locale: string, flag: string}>, voices_by_language: array<string, list<array{key: string, label: string, locale: string, flag: string}>>}
     */
    public function audioSettings(): array
    {
        $voicesByLanguage = [];

        foreach (TrainingLanguage::cases() as $language) {
            $voicesByLanguage[$language->value] = $this->speech->voiceOptions($language);
        }

        return [
            'delivery' => $this->speech->delivery()->value,
            'voices' => $this->speech->voiceOptions(),
            'voices_by_language' => $voicesByLanguage,
        ];
    }

    /**
     * What is specific to one lesson's audio: where to fetch it and which
     * languages have text to read.
     *
     * @return array{has_text_by_language: array<string, bool>, speech_url: string, has_text: bool}
     */
    public function lessonAudio(TrainingLesson $lesson): array
    {
        $hasText = [];

        foreach (TrainingLanguage::cases() as $language) {
            $hasText[$language->value] = $this->speech->lessonText($lesson, $language) !== '';
        }

        return [
            'has_text_by_language' => $hasText,
            'speech_url' => route('recruiter.training.lessons.speech', $lesson),
            'has_text' => $hasText[TrainingLanguage::default()->value],
        ];
    }

    /**
     * Groups lessons, in order, into the modules of the course page:
     * consecutive lessons with the same module form one module, and lessons
     * without one are grouped under the course title. Each module has a
     * unique anchor for the page's module tabs.
     *
     * @param  iterable<TrainingLesson>  $lessons
     * @return list<array{anchor: string, title: string, number: int, lesson_ids: list<int>}>
     */
    public function modules(iterable $lessons, string $courseTitle): array
    {
        $modules = [];
        $anchors = [];
        $current = null;

        foreach ($lessons as $lesson) {
            $title = filled($lesson->module) ? trim((string) $lesson->module) : $courseTitle;

            if ($current === null || $modules[$current]['title'] !== $title) {
                $base = 'module-'.(Str::slug($title) ?: (string) (count($modules) + 1));
                $anchor = $base;
                $suffix = 2;

                while (in_array($anchor, $anchors, true)) {
                    $anchor = $base.'-'.$suffix++;
                }

                $anchors[] = $anchor;
                $modules[] = ['anchor' => $anchor, 'title' => $title, 'number' => count($modules) + 1, 'lesson_ids' => []];
                $current = count($modules) - 1;
            }

            $modules[$current]['lesson_ids'][] = $lesson->id;
        }

        return $modules;
    }

    /**
     * One assignment row for the team and assignment lists.
     *
     * @return array<string, mixed>
     */
    public function assignmentRow(TrainingAssignment $assignment, User $viewer): array
    {
        $status = $assignment->effectiveStatus();
        $progress = $this->progress->progressFor($assignment);
        $course = $assignment->version->course;

        return [
            'id' => $assignment->id,
            'recruiter_name' => $assignment->employee->user->name ?? null,
            'employee_code' => $assignment->employee->employee_code ?? null,
            'course' => ['id' => $course->id, 'title' => $course->title],
            'category' => $course->category->name ?? null,
            'version' => $assignment->version->label(),
            'status' => $status->value,
            'status_label' => $status->label(),
            'progress_percent' => $progress['percent'],
            'lessons_completed' => $progress['completed'],
            'lessons_counted' => $progress['counted'],
            'assigned_at' => $assignment->assigned_at->toIso8601String(),
            'due_at' => $assignment->due_at?->toIso8601String(),
            'completed_at' => $assignment->completed_at?->toIso8601String(),
            'assigned_by' => $assignment->assignedBy->name ?? null,
            'can' => [
                'delete' => $viewer->can('delete', $assignment),
            ],
        ];
    }

    /**
     * A recruiter's own assignment card.
     *
     * @return array<string, mixed>
     */
    public function learnerCard(TrainingAssignment $assignment, ?int $courseNumber = null): array
    {
        $status = $assignment->effectiveStatus();
        $progress = $this->progress->progressFor($assignment);
        $course = $assignment->version->course;

        return [
            'id' => $assignment->id,
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'description' => $course->description,
            ],
            'category' => $course->category->name ?? null,
            'course_number' => $courseNumber,
            'track' => $course->track->slug ?? TrainingTrack::UNASSIGNED,
            'version' => $assignment->version->label(),
            'status' => $status->value,
            'status_label' => $status->label(),
            'progress_percent' => $progress['percent'],
            'lessons_completed' => $progress['completed'],
            'lessons_counted' => $progress['counted'],
            'lessons_total' => $progress['total'],
            'estimated_minutes' => $assignment->version->estimatedMinutes(),
            'assigned_at' => $assignment->assigned_at->toIso8601String(),
            'due_at' => $assignment->due_at?->toIso8601String(),
            'completed_at' => $assignment->completed_at?->toIso8601String(),
        ];
    }
}
