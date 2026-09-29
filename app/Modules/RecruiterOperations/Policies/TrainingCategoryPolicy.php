<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\TrainingCategory;

/**
 * Training categories (levels) are managed with recruiter.training.manage.
 */
class TrainingCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manages($user);
    }

    public function create(User $user): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, TrainingCategory $category): bool
    {
        return $this->manages($user);
    }

    public function toggle(User $user, TrainingCategory $category): bool
    {
        return $this->manages($user);
    }

    protected function manages(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value) && $user->can(Ability::ManageRecruiterTraining->value);
    }
}
