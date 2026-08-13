<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Company;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::ViewCompanies->value);
    }

    public function view(User $user, Company $company): bool
    {
        return $user->can(Ability::ViewCompanies->value);
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::ManageCompanies->value);
    }

    public function update(User $user, Company $company): bool
    {
        return $user->can(Ability::ManageCompanies->value);
    }

    /**
     * Deleting cascades to projects and their tasks, so the screen requires the
     * company to have no projects first.
     */
    public function delete(User $user, Company $company): bool
    {
        return $user->can(Ability::ManageCompanies->value)
            && $company->projects()->doesntExist();
    }
}
