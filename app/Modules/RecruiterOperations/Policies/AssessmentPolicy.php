<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\Assessment;

/**
 * Authoring needs recruiter.assessments.manage, assigning needs
 * recruiter.assessments.invite and results need recruiter.assessments.review.
 * Recruiters taking a quiz are covered by AssessmentAssignmentPolicy::take.
 */
class AssessmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manages($user);
    }

    public function create(User $user): bool
    {
        return $this->manages($user);
    }

    public function view(User $user, Assessment $assessment): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, Assessment $assessment): bool
    {
        return $this->manages($user);
    }

    public function createVersion(User $user, Assessment $assessment): bool
    {
        return $this->manages($user) && ! $assessment->isArchived();
    }

    public function archive(User $user, Assessment $assessment): bool
    {
        return $this->manages($user) && ! $assessment->isArchived();
    }

    public function restore(User $user, Assessment $assessment): bool
    {
        return $this->manages($user) && $assessment->isArchived();
    }

    public function assign(User $user, Assessment $assessment): bool
    {
        return $user->can(Ability::RecruiterAccess->value)
            && $user->can(Ability::InviteRecruiterAssessments->value)
            && $assessment->isAssignable();
    }

    public function viewResults(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value) && $user->can(Ability::ReviewRecruiterAssessments->value);
    }

    protected function manages(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value) && $user->can(Ability::ManageRecruiterAssessments->value);
    }
}
