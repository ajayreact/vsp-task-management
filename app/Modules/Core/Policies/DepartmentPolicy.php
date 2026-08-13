<?php

namespace App\Modules\Core\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::ViewDepartments->value);
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can(Ability::ViewDepartments->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::ManageDepartments->value);
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can(Ability::ManageDepartments->value);
    }

    /**
     * Deleting a department would orphan its employees and sub-departments, so
     * the screen requires it to be empty first.
     */
    public function delete(User $user, Department $department): bool
    {
        return $user->can(Ability::ManageDepartments->value)
            && $department->employees()->doesntExist()
            && $department->children()->doesntExist();
    }
}
