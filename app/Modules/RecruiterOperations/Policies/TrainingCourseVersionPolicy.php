<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\TrainingCourseVersion;

/**
 * The live version (and any unpublished draft) can change. Older versions
 * are history and stay frozen, for managers too; TrainingContentService
 * enforces the same rule.
 */
class TrainingCourseVersionPolicy
{
    public function view(User $user, TrainingCourseVersion $version): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, TrainingCourseVersion $version): bool
    {
        return $this->manages($user) && $version->isEditable();
    }

    public function publish(User $user, TrainingCourseVersion $version): bool
    {
        return $this->manages($user) && $version->isDraft();
    }

    public function archive(User $user, TrainingCourseVersion $version): bool
    {
        return $this->manages($user) && $version->isPublished();
    }

    public function delete(User $user, TrainingCourseVersion $version): bool
    {
        return $this->manages($user) && $version->isDraft();
    }

    protected function manages(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value) && $user->can(Ability::ManageRecruiterTraining->value);
    }
}
