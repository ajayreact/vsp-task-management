<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Contract;
use App\Modules\TaskManagement\Policies\Concerns\InteractsWithTaskDepartments;

class ContractPolicy
{
    use InteractsWithTaskDepartments;

    public function viewAny(User $user): bool
    {
        return $this->canAccessContracts($user);
    }

    public function view(User $user, Contract $contract): bool
    {
        return $this->canAccessContracts($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageContracts($user);
    }

    public function update(User $user, Contract $contract): bool
    {
        return $this->canManageContracts($user);
    }

    public function delete(User $user, Contract $contract): bool
    {
        return $this->canManageContracts($user);
    }

    public function share(User $user, Contract $contract): bool
    {
        return $user->can(Ability::ShareContracts->value)
            || $this->canManageContracts($user);
    }

    public function generatePdf(User $user, Contract $contract): bool
    {
        return $this->canManageContracts($user);
    }

    protected function canAccessContracts(User $user): bool
    {
        if ($user->can(Ability::ViewContracts->value) || $user->can(Ability::ManageContracts->value)) {
            return true;
        }

        if ($user->can(Ability::ManageCompanies->value)) {
            return true;
        }

        return $this->hasTaskAccess($user) && $this->isOperationsDepartment($user);
    }

    protected function canManageContracts(User $user): bool
    {
        if ($user->can(Ability::ManageContracts->value) || $user->can(Ability::ManageCompanies->value)) {
            return true;
        }

        return $this->hasTaskAccess($user) && $this->isOperationsDepartment($user);
    }
}
