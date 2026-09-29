<?php

namespace App\Modules\RecruiterOperations\Services\Assessments;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\AssessmentResult;
use App\Modules\RecruiterOperations\Enums\AttemptStatus;
use App\Modules\RecruiterOperations\Models\AssessmentAnswer;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use App\Modules\RecruiterOperations\Services\RecruiterNotifier;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Manual scoring of short answers. Nothing is marked correct automatically;
 * a reviewer awards 0 up to the question's points. Once no answer in the
 * attempt is waiting, the attempt gets its final result and the recruiter is
 * told.
 *
 * Reviewers cannot score their own attempts.
 */
class AssessmentReviewService
{
    public function __construct(
        protected AssessmentAttemptService $attempts,
        protected RecruiterNotifier $notifier,
    ) {}

    public function review(AssessmentAnswer $answer, int $points, ?string $feedback, User $reviewer): AssessmentAttempt
    {
        if (! $reviewer->can(Ability::RecruiterAccess->value) || ! $reviewer->can(Ability::ReviewRecruiterAssessments->value)) {
            throw new AuthorizationException('You cannot review recruiter assessments.');
        }

        $answer->loadMissing(['attempt', 'question']);

        if ($this->attempts->ownEmployee($reviewer)?->id === $answer->attempt->employee_id) {
            throw new AuthorizationException('You cannot review your own attempt.');
        }

        if (! $answer->needs_review) {
            throw ValidationException::withMessages(['answer' => 'Only short answers are reviewed manually.']);
        }

        $max = $answer->question->points;

        if ($points < 0 || $points > $max) {
            throw ValidationException::withMessages(['points' => "Award between 0 and {$max} points."]);
        }

        $finalized = false;

        $attempt = DB::transaction(function () use ($answer, $points, $feedback, $reviewer, &$finalized) {
            /** @var AssessmentAttempt $attempt */
            $attempt = AssessmentAttempt::query()->whereKey($answer->attempt_id)->lockForUpdate()->firstOrFail();

            if ($attempt->status === AttemptStatus::InProgress) {
                throw ValidationException::withMessages(['answer' => 'This attempt has not been submitted yet.']);
            }

            $wasPending = $attempt->result === AssessmentResult::PendingReview;

            $answer->forceFill([
                'awarded_points' => $points,
                'is_correct' => $points >= $answer->question->points,
                'reviewed_by_user_id' => $reviewer->id,
                'reviewed_at' => now(),
                'reviewer_feedback' => blank($feedback) ? null : trim((string) $feedback),
            ])->save();

            $recalculated = $this->attempts->recalculate($attempt);
            $finalized = $wasPending && $recalculated->result !== AssessmentResult::PendingReview;

            activity('recruiter-assessments')
                ->causedBy($reviewer)
                ->performedOn($recalculated)
                ->withProperties(['answer_id' => $answer->id, 'points' => $points, 'result' => $recalculated->result?->value])
                ->event('reviewed')
                ->log('Reviewed a short answer');

            return $recalculated;
        });

        if ($finalized) {
            DB::afterCommit(fn () => $this->notifier->assessmentResult($attempt, $reviewer));
        }

        return $attempt;
    }
}
