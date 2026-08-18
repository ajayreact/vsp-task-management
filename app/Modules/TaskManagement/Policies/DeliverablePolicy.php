<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Deliverable;

class DeliverablePolicy
{
    /**
     * Issuing or copying the public client share link. Super Admin is granted
     * through Gate::before; this method is the rest of the staff surface.
     */
    public function share(User $user, Deliverable $deliverable): bool
    {
        if ($user->can(Ability::ViewAllTasks->value)) {
            return true;
        }

        if ($user->employee?->id === $deliverable->submitted_by_employee_id) {
            return true;
        }

        $task = $deliverable->task;

        return $task->assigned_employee_id !== null
            && $task->assigned_employee_id === $user->employee?->id;
    }

    /**
     * Manual deletion of proof files. Submitters and assignees are not
     * authorised merely for having uploaded or holding the task. Super Admin
     * is granted through Gate::before.
     */
    public function deleteProof(User $user, Deliverable $deliverable): bool
    {
        return $user->can(Ability::ViewAllTasks->value);
    }
}
