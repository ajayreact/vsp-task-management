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

    public function viewLogoLibrary(User $user): bool
    {
        return $this->canAccessLogoLibrary($user);
    }

    public function viewLogo(User $user, Company $company): bool
    {
        return $this->canAccessLogoLibrary($user);
    }

    public function manageLogos(User $user, Company $company): bool
    {
        return $user->can(Ability::ManageCompanyLogos->value)
            || $user->can(Ability::ManageCompanies->value);
    }

    public function shareLogos(User $user, Company $company): bool
    {
        return $this->manageLogos($user, $company);
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

    protected function canAccessLogoLibrary(User $user): bool
    {
        if ($user->can(Ability::ViewCompanyLogos->value) || $user->can(Ability::ManageCompanyLogos->value)) {
            return true;
        }

        if ($user->can(Ability::ViewCompanies->value) || $user->can(Ability::ManageCompanies->value)) {
            return true;
        }

        if (! $user->can(Ability::AccessTasks->value)) {
            return false;
        }

        $user->loadMissing('employee.department');
        $code = $user->employee?->department?->code;

        return in_array($code, ['CRT', 'OPS'], true);
    }
}
