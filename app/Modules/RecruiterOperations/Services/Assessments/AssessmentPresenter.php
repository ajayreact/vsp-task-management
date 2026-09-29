<?php

namespace App\Modules\RecruiterOperations\Services\Assessments;

use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Models\Assessment;
use App\Modules\RecruiterOperations\Models\AssessmentAnswer;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use App\Modules\RecruiterOperations\Models\AssessmentOption;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;
use Illuminate\Support\Collection;

/**
 * Page props for assessments.
 *
 * Recruiter-facing payloads never contain correct options, explanations or
 * any other answer key while an attempt is in progress. After submission the
 * key is included only when the version allows review, and scores only when
 * it shows results.
 */
class AssessmentPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function settings(AssessmentVersion $version): array
    {
        return [
            'id' => $version->id,
            'label' => $version->label(),
            'version_number' => $version->version_number,
            'status' => $version->status->value,
            'status_label' => $version->status->label(),
            'instructions' => $version->instructions,
            'passing_percentage' => $version->passing_percentage,
            'time_limit_minutes' => $version->time_limit_minutes,
            'max_attempts' => $version->max_attempts,
            'randomize_questions' => $version->randomize_questions,
            'randomize_options' => $version->randomize_options,
            'show_result' => $version->show_result,
            'allow_review' => $version->allow_review,
            'published_at' => $version->published_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function assessmentSummary(Assessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'title' => $assessment->title,
            'description' => $assessment->description,
            'type' => $assessment->type->value,
            'type_label' => $assessment->type->label(),
            'status' => $assessment->status->value,
            'status_label' => $assessment->status->label(),
            'current_version_id' => $assessment->current_version_id,
            'is_assignable' => $assessment->isAssignable(),
        ];
    }

    /**
     * Full question for managers, answer key included.
     *
     * @return array<string, mixed>
     */
    public function managerQuestion(AssessmentQuestion $question): array
    {
        return [
            'id' => $question->id,
            'type' => $question->type->value,
            'type_label' => $question->type->label(),
            'prompt' => $question->prompt,
            'points' => $question->points,
            'explanation' => $question->explanation,
            'category' => $question->category,
            'sort_order' => $question->sort_order,
            'source' => $question->source->value,
            'source_label' => $question->source->label(),
            'import_batch' => $question->import_batch,
            'import_row' => $question->import_row,
            'archived' => $question->trashed(),
            'options' => $question->options->map(fn (AssessmentOption $option) => [
                'id' => $option->id,
                'key' => $option->option_key,
                'text' => $option->text,
                'is_correct' => (bool) $option->getAttribute('is_correct'),
            ])->values()->all(),
        ];
    }

    /**
     * The questions of an attempt in the order shown, without the answer key,
     * with the recruiter's saved answers.
     *
     * @return list<array<string, mixed>>
     */
    public function learnerQuestions(AssessmentAttempt $attempt): array
    {
        $questions = $this->attemptQuestions($attempt);
        $answers = $attempt->answers()->with('selectedOptions:id')->get()->keyBy('question_id');

        return $questions->map(function (AssessmentQuestion $question, int $index) use ($attempt, $answers) {
            /** @var AssessmentAnswer|null $answer */
            $answer = $answers->get($question->id);

            return [
                'id' => $question->id,
                'number' => $index + 1,
                'type' => $question->type->value,
                'prompt' => $question->prompt,
                'points' => $question->points,
                'options' => $this->orderedOptions($attempt, $question)
                    ->map(fn (AssessmentOption $option) => ['id' => $option->id, 'text' => $option->text])
                    ->values()
                    ->all(),
                'answer' => [
                    'option_ids' => $answer?->selectedOptionIds() ?? [],
                    'text' => $answer->text_answer ?? '',
                ],
            ];
        })->values()->all();
    }

    /**
     * An attempt's outcome. $forLearner applies the version's show_result and
     * allow_review settings; managers and reviewers always see everything.
     *
     * @return array<string, mixed>
     */
    public function attemptResult(AssessmentAttempt $attempt, bool $forLearner): array
    {
        $attempt->loadMissing(['version', 'answers.selectedOptions:id', 'answers.reviewer:id,name']);
        $version = $attempt->version;
        $submitted = ! $attempt->isInProgress();
        $showScore = $submitted && (! $forLearner || $version->show_result);
        $showKey = $submitted && (! $forLearner || $version->allow_review);
        $answers = $attempt->answers->keyBy('question_id');

        $showOutcome = ! $forLearner || $version->show_result || $attempt->result === AssessmentResult::PendingReview;

        $payload = [
            'id' => $attempt->id,
            'attempt_number' => $attempt->attempt_number,
            'status' => $attempt->status->value,
            'status_label' => $attempt->status->label(),
            'started_at' => $attempt->started_at->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'auto_submitted' => $attempt->auto_submitted,
            'result' => $showOutcome ? $attempt->result?->value : null,
            'result_label' => $showOutcome ? $attempt->result?->label() : null,
            'pending_review' => $attempt->result === AssessmentResult::PendingReview,
            'show_score' => $showScore,
            'show_review' => $showKey,
            'total_points' => $showScore ? $attempt->total_points : null,
            'awarded_points' => $showScore && $attempt->result !== AssessmentResult::PendingReview ? $attempt->awarded_points : null,
            'percentage' => $showScore && $attempt->percentage !== null ? (float) $attempt->percentage : null,
            'passing_percentage' => $version->passing_percentage,
            'questions' => [],
        ];

        if (! $showKey) {
            return $payload;
        }

        $payload['questions'] = $this->attemptQuestions($attempt)->map(function (AssessmentQuestion $question, int $index) use ($attempt, $answers) {
            /** @var AssessmentAnswer|null $answer */
            $answer = $answers->get($question->id);
            $selected = $answer?->selectedOptionIds() ?? [];

            return [
                'id' => $question->id,
                'number' => $index + 1,
                'type' => $question->type->value,
                'type_label' => $question->type->label(),
                'prompt' => $question->prompt,
                'points' => $question->points,
                'explanation' => $question->explanation,
                'options' => $this->orderedOptions($attempt, $question)->map(fn (AssessmentOption $option) => [
                    'id' => $option->id,
                    'text' => $option->text,
                    'is_correct' => (bool) $option->getAttribute('is_correct'),
                    'selected' => in_array($option->id, $selected, true),
                ])->values()->all(),
                'answer' => [
                    'id' => $answer?->id,
                    'text' => $answer?->text_answer,
                    'is_correct' => $answer?->is_correct,
                    'awarded_points' => $answer?->awarded_points,
                    'needs_review' => (bool) $answer?->needs_review,
                    'awaiting_review' => (bool) $answer?->isAwaitingReview(),
                    'reviewer' => $answer?->reviewer?->name,
                    'reviewed_at' => $answer?->reviewed_at?->toIso8601String(),
                    'reviewer_feedback' => $answer?->reviewer_feedback,
                ],
            ];
        })->values()->all();

        return $payload;
    }

    /**
     * $eligibility is given for the recruiter's own pages, where the result is
     * hidden if the quiz does not show results.
     *
     * @param  array{can_start: bool, reason: string|null, attempts_used: int, attempts_allowed: int, attempts_left: int, active_attempt_id: int|null}|null  $eligibility
     * @return array<string, mixed>
     */
    public function assignment(AssessmentAssignment $assignment, ?array $eligibility = null): array
    {
        $assignment->loadMissing(['version.assessment', 'employee.user:id,name', 'assignedBy:id,name']);
        $status = $assignment->effectiveStatus();
        $showOutcome = $eligibility === null || $assignment->version->show_result || $assignment->result === AssessmentResult::PendingReview;
        $attempts = $assignment->relationLoaded('attempts') ? $assignment->attempts : null;
        $latest = $attempts?->reject(fn (AssessmentAttempt $attempt) => $attempt->isInProgress())->last();

        return [
            'id' => $assignment->id,
            'assessment' => [
                'id' => $assignment->version->assessment->id,
                'title' => $assignment->version->assessment->title,
                'description' => $assignment->version->assessment->description,
            ],
            'version' => [
                'id' => $assignment->version->id,
                'label' => $assignment->version->label(),
                'passing_percentage' => $assignment->version->passing_percentage,
                'time_limit_minutes' => $assignment->version->time_limit_minutes,
                'max_attempts' => $assignment->version->max_attempts,
                'instructions' => $assignment->version->instructions,
                'question_count' => $assignment->version->questions()->count(),
            ],
            'employee' => [
                'id' => $assignment->employee_id,
                'name' => $assignment->employee->user->name ?? 'Recruiter',
            ],
            'assigned_by' => $assignment->assignedBy?->name,
            'from_training' => $assignment->training_assignment_id !== null,
            'status' => $status->value,
            'status_label' => $status->label(),
            'result' => $showOutcome ? $assignment->result?->value : null,
            'result_label' => $showOutcome ? $assignment->result?->label() : null,
            'due_at' => $assignment->due_at?->toIso8601String(),
            'assigned_at' => $assignment->assigned_at->toIso8601String(),
            'completed_at' => $assignment->completed_at?->toIso8601String(),
            'attempts_count' => $attempts?->count(),
            'latest_attempt_id' => $latest?->id,
            'eligibility' => $eligibility,
        ];
    }

    // Internals

    /**
     * @return Collection<int, AssessmentQuestion>
     */
    protected function attemptQuestions(AssessmentAttempt $attempt): Collection
    {
        $order = $attempt->layout['questions'];
        $questions = AssessmentQuestion::query()
            ->withTrashed()
            ->with('options')
            ->where('assessment_version_id', $attempt->assessment_version_id)
            ->whereIn('id', $order)
            ->get()
            ->keyBy('id');

        return collect($order)
            ->map(fn (int $id) => $questions->get($id))
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, AssessmentOption>
     */
    protected function orderedOptions(AssessmentAttempt $attempt, AssessmentQuestion $question): Collection
    {
        $order = $attempt->layout['options'][(string) $question->id] ?? $attempt->layout['options'][$question->id] ?? [];
        $options = $question->options->keyBy('id');

        $ordered = collect($order)->map(fn (int $id) => $options->get($id))->filter()->values();

        return $ordered->count() === $options->count() ? $ordered : $question->options->values();
    }
}
