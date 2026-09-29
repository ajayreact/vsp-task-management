<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Models\TrainingLesson;

/**
 * Shapes training records for the pages. Private files and audio are only
 * ever referenced by their authorized routes, never by storage URLs.
 */
class TrainingPresenter
{
    public function __construct(
        protected TrainingProgressService $progress,
        protected TrainingSpeechService $speech,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function lesson(TrainingLesson $lesson): array
    {
        $file = $lesson->file();

        return [
            'id' => $lesson->id,
            'title' => $lesson->title,
            'description' => $lesson->description,
            'body' => $lesson->body,
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
     * What the audio player needs. Voices are only those the configured
     * provider really offers, Indian English first.
     *
     * @return array{delivery: string, voices: list<array{key: string, label: string, locale: string, flag: string}>, speech_url: string, has_text: bool}
     */
    public function audio(TrainingLesson $lesson): array
    {
        return [
            'delivery' => $this->speech->delivery()->value,
            'voices' => $this->speech->voiceOptions(),
            'speech_url' => route('recruiter.training.lessons.speech', $lesson),
            'has_text' => $this->speech->lessonText($lesson) !== '',
        ];
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
    public function learnerCard(TrainingAssignment $assignment): array
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
