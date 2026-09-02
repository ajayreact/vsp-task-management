<?php

namespace App\Modules\TaskManagement\Policies\Concerns;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;

trait InteractsWithTaskDepartments
{
    protected function departmentCode(User $user): ?string
    {
        $user->loadMissing('employee.department');

        return $user->employee?->department?->code;
    }

    protected function isOperationsDepartment(User $user): bool
    {
        return $this->departmentCode($user) === 'OPS';
    }

    protected function isCreativeDepartment(User $user): bool
    {
        return $this->departmentCode($user) === 'CRT';
    }

    protected function hasTaskAccess(User $user): bool
    {
        return $user->can(Ability::AccessTasks->value);
    }
}
