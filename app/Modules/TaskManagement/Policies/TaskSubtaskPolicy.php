<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\TaskSubtask;

class TaskSubtaskPolicy
{
    public function update(User $user, TaskSubtask $subtask): bool
    {
        return $user->can('manageSubtasks', $subtask->task);
    }

    public function updateOwn(User $user, TaskSubtask $subtask): bool
    {
        return $user->employee?->id !== null
            && $subtask->assigned_employee_id === $user->employee->id;
    }

    public function delete(User $user, TaskSubtask $subtask): bool
    {
        return $user->can('manageSubtasks', $subtask->task);
    }

    public function toggle(User $user, TaskSubtask $subtask): bool
    {
        return $user->can('manageSubtasks', $subtask->task)
            || $this->updateOwn($user, $subtask);
    }
}
