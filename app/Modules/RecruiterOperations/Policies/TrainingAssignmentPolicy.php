<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;

/**
 * Assignment administration needs recruiter.training.assign. A recruiter's
 * own learning goes through TrainingProgressService, which checks ownership
 * against their employee record.
 */
class TrainingAssignmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->assigns($user);
    }

    public function create(User $user): bool
    {
        return $this->assigns($user);
    }

    /**
     * Only before the recruiter starts; anything with progress is history.
     */
    public function delete(User $user, TrainingAssignment $assignment): bool
    {
        return $this->assigns($user) && ! $assignment->isStarted();
    }

    protected function assigns(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value) && $user->can(Ability::AssignRecruiterTraining->value);
    }
}
