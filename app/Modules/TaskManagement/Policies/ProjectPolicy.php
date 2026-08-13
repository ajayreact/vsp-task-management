<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Project;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::ViewProjects->value);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->can(Ability::ViewProjects->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::ManageProjects->value);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->can(Ability::ManageProjects->value);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->can(Ability::ManageProjects->value)
            && $project->tasks()->doesntExist();
    }
}
