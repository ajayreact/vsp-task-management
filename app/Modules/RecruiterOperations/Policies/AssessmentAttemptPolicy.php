<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;

/**
 * A recruiter sees and answers only their own attempts. Reviewers
 * (recruiter.assessments.review) see every attempt and score short answers,
 * except their own.
 */
class AssessmentAttemptPolicy
{
    public function view(User $user, AssessmentAttempt $attempt): bool
    {
        return $this->owns($user, $attempt);
    }

    public function answer(User $user, AssessmentAttempt $attempt): bool
    {
        return $this->owns($user, $attempt) && $attempt->isInProgress();
    }

    public function viewAsReviewer(User $user, AssessmentAttempt $attempt): bool
    {
        return $this->reviews($user);
    }

    public function review(User $user, AssessmentAttempt $attempt): bool
    {
        return $this->reviews($user) && ! $attempt->isInProgress() && ! $this->owns($user, $attempt);
    }

    protected function owns(User $user, AssessmentAttempt $attempt): bool
    {
        return $user->can(Ability::RecruiterAccess->value)
            && $user->employee !== null
            && $attempt->isOwnedBy($user->employee);
    }

    protected function reviews(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value) && $user->can(Ability::ReviewRecruiterAssessments->value);
    }
}
