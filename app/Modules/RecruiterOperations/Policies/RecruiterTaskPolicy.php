<?php

namespace App\Modules\RecruiterOperations\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Models\RecruiterTask;

/**
 * Who may do what with a recruiter task. Whether a move is legal from the
 * task's current status is RecruiterTaskWorkflow's business, and so is the
 * identity check on assignee-only moves, which must hold even for Super Admin
 * (who passes every policy through Gate::before).
 */
class RecruiterTaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value);
    }

    /**
     * Every recruiter's tasks, not just one's own.
     */
    public function viewTeam(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value)
            && $user->can(Ability::ViewRecruiterTeam->value);
    }

    public function view(User $user, RecruiterTask $task): bool
    {
        if (! $user->can(Ability::RecruiterAccess->value)) {
            return false;
        }

        return $this->viewTeam($user)
            || $this->isAssignee($user, $task)
            || $this->isCreator($user, $task);
    }

    public function create(User $user): bool
    {
        return $user->can(Ability::RecruiterAccess->value)
            && $user->can(Ability::ManageRecruiterTasks->value);
    }

    /**
     * A cancelled task is read-only.
     */
    public function update(User $user, RecruiterTask $task): bool
    {
        return $this->manages($user, $task) && ! $task->status->isTerminal();
    }

    public function delete(User $user, RecruiterTask $task): bool
    {
        return $this->manages($user, $task);
    }

    public function reassign(User $user, RecruiterTask $task): bool
    {
        return $this->manages($user, $task);
    }

    public function cancel(User $user, RecruiterTask $task): bool
    {
        return $this->manages($user, $task);
    }

    public function reopen(User $user, RecruiterTask $task): bool
    {
        return $this->manages($user, $task);
    }

    public function accept(User $user, RecruiterTask $task): bool
    {
        return $this->isAssignee($user, $task);
    }

    public function decline(User $user, RecruiterTask $task): bool
    {
        return $this->isAssignee($user, $task);
    }

    public function complete(User $user, RecruiterTask $task): bool
    {
        return $this->isAssignee($user, $task);
    }

    public function hold(User $user, RecruiterTask $task): bool
    {
        return $this->isAssignee($user, $task) || $this->manages($user, $task);
    }

    public function resume(User $user, RecruiterTask $task): bool
    {
        return $this->isAssignee($user, $task) || $this->manages($user, $task);
    }

    /**
     * Managing a task needs recruiter.tasks.manage, and either sight of the
     * whole team or having created the task oneself.
     */
    protected function manages(User $user, RecruiterTask $task): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        return $user->can(Ability::ViewRecruiterTeam->value) || $this->isCreator($user, $task);
    }

    protected function isAssignee(User $user, RecruiterTask $task): bool
    {
        return $task->isAssignedTo($user->employee);
    }

    protected function isCreator(User $user, RecruiterTask $task): bool
    {
        return $task->created_by_user_id !== null && $task->created_by_user_id === $user->id;
    }
}
