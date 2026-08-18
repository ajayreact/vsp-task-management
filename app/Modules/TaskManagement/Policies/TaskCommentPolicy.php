<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\TaskComment;

class TaskCommentPolicy
{
    public function update(User $user, TaskComment $comment): bool
    {
        if ((int) $comment->user_id !== (int) $user->id) {
            return false;
        }

        return $user->can('view', $comment->task);
    }

    /**
     * Authors may remove their own notes. Team leads and super admins may
     * remove any comment on a task they can view.
     */
    public function delete(User $user, TaskComment $comment): bool
    {
        if (! $user->can('view', $comment->task)) {
            return false;
        }

        return (int) $comment->user_id === (int) $user->id
            || $user->can(Ability::ViewAllTasks->value);
    }
}
