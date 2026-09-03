<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\AssignmentStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

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
            || $this->hasAssignedSubtask($user, $task)
            || $task->created_by_user_id === $user->id
            || $this->isClaimable($task);
    }

    /**
     * Full admin task detail surface: reminders, recurrence, assignment
     * history, reassign, and task structure editing.
     */
    public function viewAdminDetails(User $user, Task $task): bool
    {
        if (! $this->view($user, $task)) {
            return false;
        }

        return $user->can(Ability::ViewAllTasks->value)
            || $user->can(Ability::AssignTasks->value);
    }

    public function viewAssignmentHistory(User $user, Task $task): bool
    {
        return $this->viewAdminDetails($user, $task);
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
        if (! $this->isAssignee($user, $task) || $task->status !== TaskStatus::Assigned) {
            return false;
        }

        return $task->assignments()
            ->where('employee_id', $user->employee?->id)
            ->where('status', AssignmentStatus::Pending)
            ->exists();
    }

    /**
     * Driving the task through the rest of its lifecycle. Which specific
     * transitions are legal is the state machine's business, not the policy's.
     */
    public function progress(User $user, Task $task): bool
    {
        return $this->isAssignee($user, $task) || $user->can(Ability::AssignTasks->value);
    }

    /**
     * Only the assignee may start, pause, or stop the live working timer.
     */
    public function startTimer(User $user, Task $task): bool
    {
        if (! $task->status->isWorkable() || $user->employee === null) {
            return false;
        }

        return $this->isAssignee($user, $task);
    }

    /**
     * The assignee logs manual intervals on their own work. A manager with
     * view-all may add manual time on behalf of the assignee, but cannot
     * operate the live timer controls.
     */
    public function logManualTime(User $user, Task $task): bool
    {
        if (! $task->status->isWorkable() || $user->employee === null) {
            return false;
        }

        if ($this->isAssignee($user, $task)) {
            return true;
        }

        return $user->can(Ability::ViewAllTasks->value)
            && $task->assigned_employee_id !== null;
    }

    /**
     * @deprecated Prefer startTimer or logManualTime. Kept for callers that
     *             only need to know whether any time action is available.
     */
    public function logTime(User $user, Task $task): bool
    {
        return $this->startTimer($user, $task) || $this->logManualTime($user, $task);
    }

    public function submitProof(User $user, Task $task): bool
    {
        return $this->isAssignee($user, $task)
            && in_array($task->status, [TaskStatus::InProgress, TaskStatus::Revision], true);
    }

    public function reviewProof(User $user, Task $task): bool
    {
        return $user->can(Ability::ReviewDeliverables->value)
            && in_array($task->status, [TaskStatus::InReview], true);
    }

    /**
     * Working files, not proofs. Assignee, creator, or anyone who can see
     * everyone's tasks. An open-board lurker can view the card but cannot
     * attach until they claim it.
     */
    public function attachFiles(User $user, Task $task): bool
    {
        if (! $this->view($user, $task)) {
            return false;
        }

        return $this->isAssignee($user, $task)
            || $this->hasAssignedSubtask($user, $task)
            || $task->created_by_user_id === $user->id
            || $user->can(Ability::ViewAllTasks->value);
    }

    /**
     * Task discussion follows the same collaboration surface as working-file
     * uploads.
     */
    public function comment(User $user, Task $task): bool
    {
        return $this->attachFiles($user, $task);
    }

    /**
     * Adding, editing, deleting, and reordering checklist items.
     */
    public function manageChecklist(User $user, Task $task): bool
    {
        return $this->viewAdminDetails($user, $task);
    }

    /**
     * Marking checklist items complete without changing the list structure.
     */
    public function completeChecklist(User $user, Task $task): bool
    {
        if (! $this->view($user, $task)) {
            return false;
        }

        return $this->isAssignee($user, $task)
            || $this->hasAssignedSubtask($user, $task)
            || $this->viewAdminDetails($user, $task);
    }

    /**
     * Creating, deleting, reassigning, and reordering subtasks.
     */
    public function manageSubtasks(User $user, Task $task): bool
    {
        return $this->viewAdminDetails($user, $task);
    }

    public function manageReminders(User $user, Task $task): bool
    {
        return $this->viewAdminDetails($user, $task);
    }

    public function manageRecurrence(User $user, Task $task): bool
    {
        if (! $this->viewAdminDetails($user, $task)) {
            return false;
        }

        return $task->recurrence_occurrence_number === null || $task->recurrence_occurrence_number === 0;
    }

    /**
     * The person who uploaded the file, or a manager who can see all tasks.
     */
    public function deleteAttachment(User $user, Task $task, Media $media): bool
    {
        if ($media->collection_name !== 'attachments'
            || $media->model_type !== $task->getMorphClass()
            || (int) $media->model_id !== (int) $task->id) {
            return false;
        }

        if (! $this->view($user, $task)) {
            return false;
        }

        $uploaderId = (int) ($media->getCustomProperty('uploaded_by_user_id') ?? 0);

        return $uploaderId === (int) $user->id
            || $user->can(Ability::ViewAllTasks->value);
    }

    protected function isAssignee(User $user, Task $task): bool
    {
        return $task->assigned_employee_id !== null
            && $task->assigned_employee_id === $user->employee?->id;
    }

    protected function hasAssignedSubtask(User $user, Task $task): bool
    {
        $employeeId = $user->employee?->id;

        if ($employeeId === null) {
            return false;
        }

        return $task->subtasks()->where('assigned_employee_id', $employeeId)->exists();
    }

    protected function isClaimable(Task $task): bool
    {
        return $task->assigned_employee_id === null
            && $task->status === TaskStatus::Open;
    }
}
