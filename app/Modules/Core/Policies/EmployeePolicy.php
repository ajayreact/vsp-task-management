<?php

namespace App\Modules\Core\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::ViewEmployees->value);
    }

    public function view(User $user, Employee $employee): bool
    {
        return $user->can(Ability::ViewEmployees->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::ManageEmployees->value);
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->can(Ability::ManageEmployees->value);
    }

    /**
     * Employees are never hard-deleted while they own history; the admin screen
     * marks them exited instead. Deletion stays behind the same ability so the
     * route is still guarded if it is added later.
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->can(Ability::ManageEmployees->value)
            && $employee->user_id !== $user->id;
    }
}
