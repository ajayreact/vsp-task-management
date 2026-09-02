<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\CompanyDocument;
use App\Modules\TaskManagement\Policies\Concerns\InteractsWithTaskDepartments;

class CompanyDocumentPolicy
{
    use InteractsWithTaskDepartments;

    public function viewAny(User $user): bool
    {
        return $this->canAccessDocuments($user);
    }

    public function view(User $user, CompanyDocument $document): bool
    {
        return $this->canAccessDocuments($user);
    }

    public function create(User $user): bool
    {
        return $this->canManageDocuments($user);
    }

    public function update(User $user, CompanyDocument $document): bool
    {
        return $this->canManageDocuments($user);
    }

    public function delete(User $user, CompanyDocument $document): bool
    {
        return $this->canManageDocuments($user);
    }

    public function share(User $user, CompanyDocument $document): bool
    {
        return $user->can(Ability::ShareCompanyDocuments->value)
            || $this->canManageDocuments($user);
    }

    protected function canAccessDocuments(User $user): bool
    {
        if ($user->can(Ability::ViewCompanyDocuments->value) || $user->can(Ability::ManageCompanyDocuments->value)) {
            return true;
        }

        if ($user->can(Ability::ManageCompanies->value)) {
            return true;
        }

        return $this->hasTaskAccess($user) && $this->isOperationsDepartment($user);
    }

    protected function canManageDocuments(User $user): bool
    {
        if ($user->can(Ability::ManageCompanyDocuments->value) || $user->can(Ability::ManageCompanies->value)) {
            return true;
        }

        return $this->hasTaskAccess($user) && $this->isOperationsDepartment($user);
    }
}
