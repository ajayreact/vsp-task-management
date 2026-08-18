<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\TaskChecklistItem;

class TaskChecklistItemPolicy
{
    public function update(User $user, TaskChecklistItem $item): bool
    {
        return $user->can('manageChecklist', $item->task);
    }

    public function delete(User $user, TaskChecklistItem $item): bool
    {
        return $user->can('manageChecklist', $item->task);
    }

    public function toggle(User $user, TaskChecklistItem $item): bool
    {
        return $user->can('completeChecklist', $item->task);
    }
}
