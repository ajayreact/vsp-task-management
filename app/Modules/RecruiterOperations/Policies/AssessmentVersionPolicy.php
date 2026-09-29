<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\AssessmentVersion;

/**
 * Only a draft version can change. Published and archived versions are
 * frozen, for managers too; AssessmentContentService enforces the same rule.
 */
class AssessmentVersionPolicy
{
    public function view(User $user, AssessmentVersion $version): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, AssessmentVersion $version): bool
    {
        return $this->manages($user) && $version->isDraft();
    }

    public function publish(User $user, AssessmentVersion $version): bool
    {
        return $this->manages($user) && $version->isDraft();
    }

    public function archive(User $user, AssessmentVersion $version): bool
    {
        return $this->manages($user) && $version->isPublished();
    }

    public function delete(User $user, AssessmentVersion $version): bool
    {
        return $this->manages($user) && $version->isDraft();
    }

    protected function manages(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value) && $user->can(Ability::ManageRecruiterAssessments->value);
    }
}
