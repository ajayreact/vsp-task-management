<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\DeliverableStatus;
use App\Modules\TaskManagement\Models\Deliverable;

class DeliverablePolicy
{
    /**
     * Issuing or copying the public client share link. Super Admin is granted
     * through Gate::before; managers need tasks.view_all. The assignee may
     * share versions they have submitted on their task.
     */
    public function share(User $user, Deliverable $deliverable): bool
    {
        if ($user->can(Ability::ViewAllTasks->value)) {
            return true;
        }

        $employee = $user->employee;

        if ($employee === null) {
            return false;
        }

        $task = $deliverable->task;

        if ($task->assigned_employee_id !== $employee->id) {
            return false;
        }

        return in_array($deliverable->status, [
            DeliverableStatus::Submitted,
            DeliverableStatus::InReview,
            DeliverableStatus::Approved,
        ], true);
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
