<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\RecruiterDailyActivity;

/**
 * Recruiters see and change only their own activity log, and only within the
 * self-service window. Seeing the team needs recruiter.team.view; correcting
 * someone else's record also needs recruiter.tasks.manage, the existing
 * permission for recruiter lead write access.
 *
 * RecruiterDailyActivityService repeats the ownership check against the
 * actor's employee record, because Super Admin passes every policy through
 * Gate::before.
 */
class RecruiterDailyActivityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value);
    }

    public function viewTeam(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value)
            && $user->can(Ability::ViewRecruiterTeam->value);
    }

    public function view(User $user, RecruiterDailyActivity $activity): bool
    {
        return $this->viewTeam($user)
            || ($this->viewAny($user) && $activity->isOwnedBy($user->employee));
    }

    /**
     * An activity always belongs to the person recording it, so recording one
     * needs an employee profile.
     */
    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->employee !== null;
    }

    public function update(User $user, RecruiterDailyActivity $activity): bool
    {
        return $this->correctsTeam($user) || $this->ownsEditable($user, $activity);
    }

    public function delete(User $user, RecruiterDailyActivity $activity): bool
    {
        return $this->correctsTeam($user) || $this->ownsEditable($user, $activity);
    }

    /**
     * Record dates outside the recruiter's own window.
     */
    public function correctsTeam(User $user): bool
    {
        return $this->viewTeam($user) && $user->can(Ability::ManageRecruiterTasks->value);
    }

    protected function ownsEditable(User $user, RecruiterDailyActivity $activity): bool
    {
        return $this->viewAny($user)
            && $activity->isOwnedBy($user->employee)
            && $activity->isWithinSelfServiceWindow();
    }
}
