<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\AssignmentStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;

/**
 * Single source of truth for which workflow actions appear on a task detail page.
 * Uses explicit business rules — never the super-admin Gate::before bypass.
 */
class TaskActionResolver
{
    /**
     * @return array{
     *     can_accept: bool,
     *     can_decline: bool,
     *     can_claim: bool,
     *     can_reassign: bool,
     *     can_move_to_open_board: bool,
     *     can_assign: bool
     * }
     */
    public function resolve(Task $task, User $user): array
    {
        $isAssignee = $this->isAssignee($user, $task);
        $canManageAssignment = $this->canManageAssignment($user);
        $isUnstarted = $task->status->isUnstarted();
        $pendingDirectOffer = $this->hasPendingDirectOffer($task, $user);
        $canClaim = $this->canClaim($task, $user);

        $canAccept = $isAssignee && $task->status === TaskStatus::Assigned && $pendingDirectOffer;
        $canDecline = $canAccept;
        $canReassign = $canManageAssignment && $isUnstarted;
        $canMoveToOpenBoard = $canManageAssignment
            && $isUnstarted
            && $task->status !== TaskStatus::Open;

        return [
            'can_accept' => $canAccept,
            'can_decline' => $canDecline,
            'can_claim' => $canClaim,
            'can_reassign' => $canReassign,
            'can_move_to_open_board' => $canMoveToOpenBoard,
            // Legacy keys used by existing Inertia pages.
            'can_assign' => $canReassign || $canMoveToOpenBoard,
        ];
    }

    /**
     * Mirrors TaskPolicy::respond without Gate::before.
     */
    public function canRespond(User $user, Task $task): bool
    {
        return $this->isAssignee($user, $task)
            && $task->status === TaskStatus::Assigned
            && $this->hasPendingDirectOffer($task, $user);
    }

    /**
     * Mirrors TaskPolicy::claim without Gate::before.
     */
    public function canClaim(Task $task, User $user): bool
    {
        if ($user->employee === null || ! $user->can(Ability::AccessTasks->value)) {
            return false;
        }

        return $this->isClaimable($task);
    }

    /**
     * Mirrors TaskPolicy::assign without Gate::before.
     */
    public function canAssign(Task $task, User $user): bool
    {
        return $this->canManageAssignment($user) && $task->status->isUnstarted();
    }

    protected function canManageAssignment(User $user): bool
    {
        return $user->can(Ability::AssignTasks->value);
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

    protected function hasPendingDirectOffer(Task $task, User $user): bool
    {
        $employeeId = $user->employee?->id;

        if ($employeeId === null) {
            return false;
        }

        return $task->assignments()
            ->where('employee_id', $employeeId)
            ->where('status', AssignmentStatus::Pending)
            ->exists();
    }
}
