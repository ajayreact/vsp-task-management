<?php

namespace App\Modules\Core\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::ViewRoles->value);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can(Ability::ViewRoles->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::ManageRoles->value);
    }

    /**
     * Super admin is the escape hatch that Gate::before keys off. Letting it be
     * edited or deleted through the UI would make it possible to lock everyone
     * out of the permission screens.
     */
    public function update(User $user, Role $role): bool
    {
        return $user->can(Ability::ManageRoles->value)
            && $role->name !== SystemRole::SuperAdmin->value;
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->update($user, $role)
            && ! $this->isSystemRole($role)
            && $role->users()->doesntExist();
    }

    protected function isSystemRole(Role $role): bool
    {
        return SystemRole::tryFrom($role->name) !== null;
    }
}
