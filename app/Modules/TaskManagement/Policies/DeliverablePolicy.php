<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Deliverable;

class DeliverablePolicy
{
    /**
     * Issuing or copying the public client share link. Super Admin is granted
     * through Gate::before; staff need tasks.view_all.
     */
    public function share(User $user, Deliverable $deliverable): bool
    {
        return $user->can(Ability::ViewAllTasks->value);
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
