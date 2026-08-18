<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\TaskReminder;

class TaskReminderPolicy
{
    public function delete(User $user, TaskReminder $reminder): bool
    {
        if (! $reminder->isPending()) {
            return false;
        }

        return $user->can('manageReminders', $reminder->task);
    }
}
