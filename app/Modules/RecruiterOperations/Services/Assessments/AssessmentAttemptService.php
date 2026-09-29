<?php

namespace App\Modules\RecruiterOperations\Services\Assessments;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\AssessmentAssignmentStatus;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Enums\AttemptStatus;
use App\Modules\RecruiterOperations\Enums\QuestionType;
use App\Modules\RecruiterOperations\Models\AssessmentAnswer;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Recruiter attempts. Everything that decides a score happens here, on the
 * server:
 *
 * - only the assigned recruiter can start, answer or submit, and only while
 *   the attempt is in progress; submitted answers never change;
 * - the deadline is started_at + time limit, set by the server; saves after
 *   it (plus a short grace period) are refused and the attempt is submitted
 *   with the answers already saved;
 * - one attempt at a time, up to the version's maximum; no new attempt once
 *   passed or while one waits for review;
 * - the question and option order shown is kept on the attempt.
 *
 * Expired attempts are finalized when next touched (page load, save, submit).
 *
 * @phpstan-type AnswerInput array<int|string, array{option_ids?: list<int|string>|null, text?: string|null}>
 */
class AssessmentAttemptService
{
    public const MAX_TEXT_LENGTH = 5000;

    /**
     * Whether the recruiter may start a new attempt, and why not.
     *
     * @return array{can_start: bool, reason: string|null, attempts_used: int, attempts_allowed: int, attempts_left: int, active_attempt_id: int|null}
     */
    public function eligibility(AssessmentAssignment $assignment): array
    {
        $assignment->loadMissing(['version', 'attempts']);
        $attempts = $assignment->attempts;
        $allowed = $assignment->version->max_attempts;
        $used = $attempts->count();
        $active = $attempts->first(fn (AssessmentAttempt $attempt) => $attempt->isInProgress());

        $reason = match (true) {
            $active !== null => null,
            $attempts->contains(fn (AssessmentAttempt $attempt) => $attempt->result === AssessmentResult::Passed) => $assignment->version->show_result
                ? 'You have already passed this assessment.'
                : 'This assessment is complete.',
            $attempts->contains(fn (AssessmentAttempt $attempt) => $attempt->result === AssessmentResult::PendingReview) => 'Your last attempt is waiting for review.',
            $used >= $allowed => "You have used all {$allowed} attempts.",
            default => null,
        };

        return [
            'can_start' => $active === null && $reason === null,
            'reason' => $reason,
            'attempts_used' => $used,
            'attempts_allowed' => $allowed,
            'attempts_left' => max(0, $allowed - $used),
            'active_attempt_id' => $active?->id,
        ];
    }

    /**
     * Starts a new attempt, or returns the one already in progress.
     */
    public function start(AssessmentAssignment $assignment, User $actor): AssessmentAttempt
    {
        $this->ensureOwner($assignment->employee_id, $actor);

        return DB::transaction(function () use ($assignment) {
            /** @var AssessmentAssignment $locked */
            $locked = AssessmentAssignment::query()->whereKey($assignment->id)->lockForUpdate()->firstOrFail();

            foreach ($locked->attempts()->where('status', AttemptStatus::InProgress->value)->get() as $open) {
                if ($open->hasExpired()) {
                    $this->finalize($open, true);
                } else {
                    return $open;
                }
            }

            $locked->unsetRelation('attempts');
            $eligibility = $this->eligibility($locked);

            if (! $eligibility['can_start']) {
                throw ValidationException::withMessages(['attempt' => $eligibility['reason'] ?? 'You cannot start this assessment.']);
            }

            $version = $locked->version()->with('questions.options')->firstOrFail();
            $now = now();

            $attempt = new AssessmentAttempt;
            $attempt->forceFill([
                'assignment_id' => $locked->id,
                'assessment_version_id' => $version->id,
                'employee_id' => $locked->employee_id,
                'attempt_number' => $eligibility['attempts_used'] + 1,
                'status' => AttemptStatus::InProgress,
                'active_assignment_id' => $locked->id,
                'layout' => $this->layout($version->questions, $version->randomize_questions, $version->randomize_options),
                'started_at' => $now,
                'expires_at' => $version->time_limit_minutes !== null ? $now->copy()->addMinutes($version->time_limit_minutes) : null,
                'total_points' => (int) $version->questions->sum('points'),
            ])->save();

            $locked->forceFill([
                'status' => AssessmentAssignmentStatus::InProgress,
                'started_at' => $locked->started_at ?? $now,
            ])->save();

            return $attempt;
        });
    }

    /**
     * Autosave. Refused once the attempt is submitted or out of time.
     *
     * @param  AnswerInput  $answers
     */
    public function saveAnswers(AssessmentAttempt $attempt, array $answers, User $actor): AssessmentAttempt
    {
        $this->ensureOwner($attempt->employee_id, $actor);

        $expired = DB::transaction(function () use ($attempt, $answers) {
            $locked = $this->lock($attempt);

            if ($locked->hasExpired()) {
                $this->finalize($locked, true);

                return true;
            }

            $this->writeAnswers($locked, $answers);

            return false;
        });

        if ($expired) {
            throw ValidationException::withMessages(['attempt' => 'Time is up. Your attempt was submitted with the answers saved before the deadline.']);
        }

        return $attempt->refresh();
    }

    /**
     * Saves the final answers and scores the attempt. After the deadline the
     * incoming answers are ignored and the saved ones are scored.
     *
     * @param  AnswerInput  $answers
     */
    public function submit(AssessmentAttempt $attempt, array $answers, User $actor): AssessmentAttempt
    {
        $this->ensureOwner($attempt->employee_id, $actor);

        DB::transaction(function () use ($attempt, $answers) {
            $locked = $this->lock($attempt);

            if ($locked->hasExpired()) {
                $this->finalize($locked, true);

                return;
            }

            $this->writeAnswers($locked, $answers);
            $this->finalize($locked, false);
        });

        return $attempt->refresh();
    }

    /**
     * Submits an attempt whose time ran out. Safe to call on any attempt.
     */
    public function finalizeIfExpired(AssessmentAttempt $attempt): bool
    {
        if (! $attempt->isInProgress() || ! $attempt->hasExpired()) {
            return false;
        }

        return DB::transaction(function () use ($attempt) {
            /** @var AssessmentAttempt $locked */
            $locked = AssessmentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isInProgress() || ! $locked->hasExpired()) {
                return false;
            }

            $this->finalize($locked, true);
            $attempt->refresh();

            return true;
        });
    }

    /**
     * Finalizes every expired attempt of an assignment.
     */
    public function sweep(AssessmentAssignment $assignment): void
    {
        $assignment->loadMissing('attempts');
        $changed = false;

        foreach ($assignment->attempts as $attempt) {
            $changed = $this->finalizeIfExpired($attempt) || $changed;
        }

        if ($changed) {
            $assignment->refresh();
            $assignment->load('attempts');
        }
    }

    /**
     * Recomputes a submitted attempt from its stored answers, for example once
     * a reviewer has scored the short answers.
     */
    public function recalculate(AssessmentAttempt $attempt): AssessmentAttempt
    {
        $attempt->load(['answers', 'version']);

        $awarded = (int) $attempt->answers->sum(fn (AssessmentAnswer $answer) => (int) $answer->awarded_points);
        $pending = $attempt->answers->contains(fn (AssessmentAnswer $answer) => $answer->isAwaitingReview());
        $result = AssessmentScorer::result($awarded, $attempt->total_points, $attempt->version->passing_percentage, $pending);

        $attempt->forceFill([
            'awarded_points' => $awarded,
            'percentage' => $pending ? null : AssessmentScorer::percentage($awarded, $attempt->total_points),
            'result' => $result,
            'scored_at' => $pending ? null : now(),
        ])->save();

        $this->refreshAssignment($attempt->assignment()->firstOrFail());

        return $attempt;
    }

    /**
     * Brings the assignment's status and result in line with its attempts.
     */
    public function refreshAssignment(AssessmentAssignment $assignment): void
    {
        $assignment->load(['attempts', 'version']);
        $finished = $assignment->attempts->reject(fn (AssessmentAttempt $attempt) => $attempt->isInProgress());

        if ($finished->isEmpty()) {
            return;
        }

        [$status, $result] = match (true) {
            $finished->contains(fn (AssessmentAttempt $a) => $a->result === AssessmentResult::Passed) => [AssessmentAssignmentStatus::Completed, AssessmentResult::Passed],
            $finished->contains(fn (AssessmentAttempt $a) => $a->result === AssessmentResult::PendingReview) => [AssessmentAssignmentStatus::InProgress, AssessmentResult::PendingReview],
            $assignment->attempts->count() >= $assignment->version->max_attempts => [AssessmentAssignmentStatus::Completed, AssessmentResult::Failed],
            default => [AssessmentAssignmentStatus::InProgress, AssessmentResult::Failed],
        };

        $assignment->forceFill([
            'status' => $status,
            'result' => $result,
            'completed_at' => $status === AssessmentAssignmentStatus::Completed ? ($assignment->completed_at ?? now()) : null,
        ])->save();
    }

    /**
     * Question order and option order for a new attempt. True/false options
     * always stay True, False.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, AssessmentQuestion>  $questions
     * @return array{questions: list<int>, options: array<int|string, list<int>>}
     */
    public function layout(Collection $questions, bool $shuffleQuestions, bool $shuffleOptions): array
    {
        $questionIds = $questions->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        if ($shuffleQuestions) {
            shuffle($questionIds);
        }

        $options = [];

        foreach ($questions as $question) {
            $ids = $question->options->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

            if ($shuffleOptions && $question->type !== QuestionType::TrueFalse) {
                shuffle($ids);
            }

            $options[(string) $question->id] = $ids;
        }

        return ['questions' => $questionIds, 'options' => $options];
    }

    public function ownEmployee(User $actor): ?Employee
    {
        return Employee::query()->where('user_id', $actor->id)->first();
    }

    // Internals

    protected function ensureOwner(int $employeeId, User $actor): void
    {
        $employee = $this->ownEmployee($actor);

        if ($employee === null || $employee->id !== $employeeId) {
            throw new AuthorizationException('This assessment belongs to another recruiter.');
        }
    }

    protected function lock(AssessmentAttempt $attempt): AssessmentAttempt
    {
        /** @var AssessmentAttempt $locked */
        $locked = AssessmentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

        if (! $locked->isInProgress()) {
            throw ValidationException::withMessages(['attempt' => 'This attempt has already been submitted, so its answers cannot change.']);
        }

        return $locked;
    }

    /**
     * @param  AnswerInput  $answers
     */
    protected function writeAnswers(AssessmentAttempt $attempt, array $answers): void
    {
        if ($answers === []) {
            return;
        }

        $questions = AssessmentQuestion::query()
            ->withTrashed()
            ->with('options:id,question_id')
            ->where('assessment_version_id', $attempt->assessment_version_id)
            ->get()
            ->keyBy('id');
        $inAttempt = $attempt->layout['questions'];
        $now = now();

        foreach ($answers as $questionId => $input) {
            $questionId = (int) $questionId;
            $question = $questions->get($questionId);

            if ($question === null || ! in_array($questionId, $inAttempt, true)) {
                throw ValidationException::withMessages(['answers' => 'An answer refers to a question that is not part of this attempt.']);
            }

            $optionIds = AssessmentScorer::normalizeSelection($input['option_ids'] ?? []);
            $text = trim((string) ($input['text'] ?? ''));

            if ($question->type->usesOptions()) {
                $valid = $question->options->pluck('id')->map(fn ($id) => (int) $id)->all();

                if (array_diff($optionIds, $valid) !== []) {
                    throw ValidationException::withMessages(['answers' => 'An answer refers to an option that does not belong to its question.']);
                }

                if (! $question->type->allowsSeveralCorrect() && count($optionIds) > 1) {
                    throw ValidationException::withMessages(['answers' => 'Choose only one option for single-choice and true/false questions.']);
                }

                $text = '';
            } else {
                if (mb_strlen($text) > self::MAX_TEXT_LENGTH) {
                    throw ValidationException::withMessages(['answers' => 'Answers can be at most '.self::MAX_TEXT_LENGTH.' characters.']);
                }

                $optionIds = [];
            }

            /** @var AssessmentAnswer|null $answer */
            $answer = AssessmentAnswer::query()->where('attempt_id', $attempt->id)->where('question_id', $questionId)->first();

            if ($optionIds === [] && $text === '') {
                $answer?->delete();

                continue;
            }

            $answer ??= (new AssessmentAnswer)->forceFill(['attempt_id' => $attempt->id, 'question_id' => $questionId]);
            $answer->forceFill(['text_answer' => $text === '' ? null : $text, 'answered_at' => $now])->save();
            $answer->selectedOptions()->sync($optionIds);
        }
    }

    /**
     * Scores and closes an attempt. An attempt that ran out of time with
     * nothing answered is recorded as abandoned (0%).
     */
    protected function finalize(AssessmentAttempt $attempt, bool $auto): void
    {
        $questions = AssessmentQuestion::query()
            ->withTrashed()
            ->with('options')
            ->where('assessment_version_id', $attempt->assessment_version_id)
            ->whereIn('id', $attempt->layout['questions'])
            ->get();
        $answers = $attempt->answers()->with('selectedOptions:id')->get()->keyBy('question_id');
        $version = $attempt->version()->firstOrFail();
        $total = (int) $questions->sum('points');
        $awarded = 0;
        $pending = false;

        foreach ($questions as $question) {
            /** @var AssessmentAnswer|null $answer */
            $answer = $answers->get($question->id);

            if ($answer === null) {
                continue;
            }

            if ($question->type->needsManualReview()) {
                $hasText = trim((string) $answer->text_answer) !== '';
                $answer->forceFill([
                    'is_correct' => null,
                    'awarded_points' => $hasText ? null : 0,
                    'needs_review' => $hasText,
                ])->save();
                $pending = $pending || $hasText;

                continue;
            }

            $points = AssessmentScorer::pointsFor($question->type, $question->points, $question->correctOptionIds(), $answer->selectedOptionIds());
            $answer->forceFill([
                'is_correct' => $points > 0,
                'awarded_points' => $points,
                'needs_review' => false,
            ])->save();
            $awarded += $points;
        }

        $abandoned = $auto && $answers->isEmpty();

        $attempt->forceFill([
            'status' => $abandoned ? AttemptStatus::Abandoned : AttemptStatus::Submitted,
            'active_assignment_id' => null,
            'submitted_at' => now(),
            'auto_submitted' => $auto,
            'total_points' => $total,
            'awarded_points' => $awarded,
            'percentage' => $pending ? null : AssessmentScorer::percentage($awarded, $total),
            'result' => AssessmentScorer::result($awarded, $total, $version->passing_percentage, $pending),
            'scored_at' => $pending ? null : now(),
        ])->save();

        $this->refreshAssignment($attempt->assignment()->firstOrFail());
    }
}
