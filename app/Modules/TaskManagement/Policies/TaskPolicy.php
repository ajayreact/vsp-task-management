<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::AccessTasks->value);
    }

    /**
     * Without `tasks.view_all` a person sees their own work and whatever is
     * sitting unclaimed on the open board, which is what they need in order to
     * pick something up.
     */
    public function view(User $user, Task $task): bool
    {
        if (! $user->can(Ability::AccessTasks->value)) {
            return false;
        }

        return $user->can(Ability::ViewAllTasks->value)
            || $this->isAssignee($user, $task)
            || $task->created_by_user_id === $user->id
            || $this->isClaimable($task);
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::ManageTasks->value);
    }

    /**
     * Editing the details of someone else's task needs the wider permission;
     * the assignee can always edit their own.
     */
    public function update(User $user, Task $task): bool
    {
        if (! $user->can(Ability::ManageTasks->value)) {
            return false;
        }

        return $user->can(Ability::ViewAllTasks->value) || $this->isAssignee($user, $task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can(Ability::ManageTasks->value)
            && $task->status->isUnstarted();
    }

    /**
     * Handing work to another person, and moving it back to the open board.
     */
    public function assign(User $user, Task $task): bool
    {
        return $user->can(Ability::AssignTasks->value) && $task->status->isUnstarted();
    }

    public function claim(User $user, Task $task): bool
    {
        return $user->can(Ability::AccessTasks->value)
            && $user->employee !== null
            && $this->isClaimable($task);
    }

    /**
     * Only the person actually holding the offer may answer it — not their
     * manager, and not an admin.
     */
    public function respond(User $user, Task $task): bool
    {
        return $this->isAssignee($user, $task);
    }

    /**
     * Driving the task through the rest of its lifecycle. Which specific
     * transitions are legal is the state machine's business, not the policy's.
     */
    public function progress(User $user, Task $task): bool
    {
        return $this->isAssignee($user, $task) || $user->can(Ability::AssignTasks->value);
    }

    protected function isAssignee(User $user, Task $task): bool
    {
        return $task->assigned_employee_id !== null
            && $task->assigned_employee_id === $user->employee?->id;
    }

    protected function isClaimable(Task $task): bool
    {
        return $task->assigned_employee_id === null
            && $task->status === TaskStatus::Open;
    }
}
