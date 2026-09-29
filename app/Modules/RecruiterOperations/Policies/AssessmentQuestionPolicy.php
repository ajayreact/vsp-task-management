<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\AssessmentQuestion;

/**
 * Question Bank items and draft-version questions can change; questions of
 * published or archived versions cannot.
 */
class AssessmentQuestionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->manages($user);
    }

    public function create(User $user): bool
    {
        return $this->manages($user);
    }

    public function import(User $user): bool
    {
        return $this->manages($user);
    }

    public function view(User $user, AssessmentQuestion $question): bool
    {
        return $this->manages($user);
    }

    public function update(User $user, AssessmentQuestion $question): bool
    {
        return $this->manages($user) && $question->isEditable();
    }

    public function delete(User $user, AssessmentQuestion $question): bool
    {
        return $this->manages($user) && $question->isEditable();
    }

    public function duplicate(User $user, AssessmentQuestion $question): bool
    {
        return $this->manages($user) && $question->isInBank() && ! $question->trashed();
    }

    public function restore(User $user, AssessmentQuestion $question): bool
    {
        return $this->manages($user) && $question->isInBank() && $question->trashed();
    }

    protected function manages(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value) && $user->can(Ability::ManageRecruiterAssessments->value);
    }
}
