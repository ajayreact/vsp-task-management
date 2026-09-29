<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\TrainingCourse;

/**
 * Every recruiter can open their own training. Authoring needs
 * recruiter.training.manage, assigning needs recruiter.training.assign, and
 * seeing the whole team's progress needs recruiter.team.view.
 */
class TrainingCoursePolicy
{
    /**
     * The learner side: My Training and assigned courses.
     */
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value);
    }

    public function manage(User $user): bool
    {
        return $this->viewAny($user) && $user->can(Ability::ManageRecruiterTraining->value);
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function view(User $user, TrainingCourse $course): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, TrainingCourse $course): bool
    {
        return $this->manage($user);
    }

    public function archive(User $user, TrainingCourse $course): bool
    {
        return $this->manage($user) && ! $course->isArchived();
    }

    public function restore(User $user, TrainingCourse $course): bool
    {
        return $this->manage($user) && $course->isArchived();
    }

    public function createVersion(User $user, TrainingCourse $course): bool
    {
        return $this->manage($user);
    }

    public function assign(User $user): bool
    {
        return $this->viewAny($user) && $user->can(Ability::AssignRecruiterTraining->value);
    }

    public function viewTeam(User $user): bool
    {
        return $this->viewAny($user) && $user->can(Ability::ViewRecruiterTeam->value);
    }
}
