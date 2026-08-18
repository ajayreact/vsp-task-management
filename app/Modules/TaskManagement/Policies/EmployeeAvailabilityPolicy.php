<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\EmployeeAvailability;

class EmployeeAvailabilityPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::AccessTasks->value);
    }

    public function manageFor(User $user, Employee $employee): bool
    {
        return $user->employee?->id === $employee->id
            || $user->can(Ability::ManageCapacity->value);
    }

    public function delete(User $user, EmployeeAvailability $availability): bool
    {
        return $this->manageFor($user, $availability->employee);
    }
}
