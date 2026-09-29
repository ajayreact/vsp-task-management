<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;

/**
 * Assignment administration needs recruiter.assessments.invite. Taking a quiz
 * is for the assigned recruiter only; AssessmentAttemptService checks the
 * same ownership against the employee record.
 */
class AssessmentAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->invites($user);
    }

    public function create(User $user): bool
    {
        return $this->invites($user);
    }

    /**
     * Only while nothing has been attempted; attempts are kept as history.
     */
    public function delete(User $user, AssessmentAssignment $assignment): bool
    {
        return $this->invites($user) && ! $assignment->attempts()->exists();
    }

    public function take(User $user, AssessmentAssignment $assignment): bool
    {
        return $user->can(Ability::RecruiterAccess->value)
            && $user->employee !== null
            && $assignment->isOwnedBy($user->employee);
    }

    protected function invites(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value) && $user->can(Ability::InviteRecruiterAssessments->value);
    }
}
