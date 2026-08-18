<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\AssignmentAction;
use App\Modules\TaskManagement\Enums\AssignmentMode;
use App\Modules\TaskManagement\Enums\AssignmentStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Exceptions\TaskWorkflowException;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every change to who holds a task and what state it is in goes through here,
 * so the rules exist once rather than in each controller that touches a task.
 */
class TaskWorkflow
{
    public function __construct(
        protected TaskNotifier $notifier,
        protected RecurringTaskService $recurring,
    ) {}

    /**
     * Put a task on the open board for anyone eligible to claim.
     */
    public function publishToBoard(Task $task, User $actor): Task
    {
        $withdrawn = collect();

        $task = DB::transaction(function () use ($task, $actor, &$withdrawn) {
            $withdrawn = $this->withdrawPendingOffers($task);

            $task->forceFill([
                'assignment_mode' => AssignmentMode::Open,
                'assigned_employee_id' => null,
            ]);

            return $this->settle($task, TaskStatus::Open, $actor);
        });

        $this->notifier->taskPublished($task, $actor, $withdrawn);

        return $task;
    }

    /**
     * Hand the task to a specific person, who then has to accept it.
     */
    public function assign(Task $task, Employee $employee, User $actor): Task
    {
        $withdrawn = collect();

        $task = DB::transaction(function () use ($task, $employee, $actor, &$withdrawn) {
            if (! $task->status->isUnstarted()) {
                throw TaskWorkflowException::alreadyStarted();
            }

            if (! $employee->status->isAssignable()) {
                throw TaskWorkflowException::employeeUnavailable();
            }

            $withdrawn = $this->withdrawPendingOffers($task);

            TaskAssignment::create([
                'tm_task_id' => $task->id,
                'employee_id' => $employee->id,
                'assigned_by_user_id' => $actor->id,
                'mode' => AssignmentAction::Direct,
                'status' => AssignmentStatus::Pending,
            ]);

            $task->forceFill([
                'assignment_mode' => AssignmentMode::Direct,
                'assigned_employee_id' => $employee->id,
            ]);

            return $this->settle($task, TaskStatus::Assigned, $actor);
        });

        $this->notifier->taskAssigned($task, $employee, $actor, $withdrawn);

        return $task;
    }

    /**
     * Take an unclaimed task off the open board. The row is locked for the
     * duration so that two people clicking at once cannot both win.
     */
    public function claim(Task $task, Employee $employee, User $actor): Task
    {
        $task = DB::transaction(function () use ($task, $employee, $actor) {
            $fresh = Task::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();

            if ($fresh->assigned_employee_id !== null || $fresh->status !== TaskStatus::Open) {
                throw TaskWorkflowException::alreadyClaimed();
            }

            if (! $employee->status->isAssignable()) {
                throw TaskWorkflowException::employeeUnavailable();
            }

            TaskAssignment::create([
                'tm_task_id' => $fresh->id,
                'employee_id' => $employee->id,
                'assigned_by_user_id' => null,
                'mode' => AssignmentAction::Claim,
                'status' => AssignmentStatus::Accepted,
                'responded_at' => now(),
            ]);

            $fresh->forceFill(['assigned_employee_id' => $employee->id]);

            return $this->moveTo($fresh, TaskStatus::Accepted, $actor);
        });

        $this->notifier->taskClaimed($task, $actor);

        return $task;
    }

    public function accept(Task $task, Employee $employee, User $actor): Task
    {
        $assigner = null;

        $task = DB::transaction(function () use ($task, $employee, $actor, &$assigner) {
            $offer = $this->offerFor($task, $employee);
            $assigner = $offer->assignedBy;

            $offer->update([
                'status' => AssignmentStatus::Accepted,
                'responded_at' => now(),
            ]);

            return $this->moveTo($task, TaskStatus::Accepted, $actor);
        });

        $this->notifier->taskAccepted($task, $actor, $assigner);

        return $task;
    }

    /**
     * Declining returns the task to the open board rather than leaving it
     * parked on someone who has said no.
     */
    public function decline(Task $task, Employee $employee, User $actor, ?string $reason = null): Task
    {
        $assigner = null;

        $task = DB::transaction(function () use ($task, $employee, $actor, $reason, &$assigner) {
            $offer = $this->offerFor($task, $employee);
            $assigner = $offer->assignedBy;

            $offer->update([
                'status' => AssignmentStatus::Declined,
                'responded_at' => now(),
                'decline_reason' => $reason,
            ]);

            $task->forceFill([
                'assignment_mode' => AssignmentMode::Open,
                'assigned_employee_id' => null,
            ]);

            return $this->moveTo($task, TaskStatus::Open, $actor);
        });

        $this->notifier->taskDeclined($task, $actor, $assigner, $reason);

        return $task;
    }

    /**
     * A plain status move, for the transitions that are not about assignment.
     */
    public function transition(Task $task, TaskStatus $target, User $actor): Task
    {
        $from = $task->status;

        $task = DB::transaction(function () use ($task, $target, $actor) {
            $this->guardTransition($task, $target);

            return $this->moveTo($task, $target, $actor);
        });

        if ($from !== $target) {
            if ($target === TaskStatus::InProgress) {
                $this->notifier->taskInProgress($task, $actor);
            }

            if ($target === TaskStatus::Completed) {
                $this->notifier->taskCompleted($task, $actor);
                $this->recurring->generateNextOccurrence($task->refresh());
            }
        }

        return $task;
    }

    protected function guardTransition(Task $task, TaskStatus $target): void
    {
        if (! $task->status->canTransitionTo($target)) {
            throw TaskWorkflowException::cannotTransition($task->status, $target);
        }
    }

    /**
     * Used by the assignment operations, where the task may already be in the
     * target state because only the holder is changing — reassigning someone
     * else's pending task, or republishing something already on the board.
     * That is a legitimate move even though the state machine has no edge from
     * a state to itself.
     */
    protected function settle(Task $task, TaskStatus $target, User $actor): Task
    {
        if ($task->status !== $target) {
            $this->guardTransition($task, $target);
        }

        return $this->moveTo($task, $target, $actor);
    }

    /**
     * Writes the new status, the timestamps that go with it, and the history
     * row. Callers have already validated the move.
     */
    protected function moveTo(Task $task, TaskStatus $target, User $actor): Task
    {
        $from = $task->status;

        $task->status = $target;

        if ($target === TaskStatus::InProgress && $task->started_at === null) {
            $task->started_at = now();
        }

        if ($target === TaskStatus::Completed) {
            $task->completed_at = now();
        }

        $task->save();

        // A handover within the same state is not a status change; who holds
        // the task is the assignment table's business, not the timeline's.
        if ($from !== $target) {
            $task->statusHistory()->create([
                'from_status' => $from,
                'to_status' => $target,
                'changed_by_user_id' => $actor->id,
                'changed_at' => now(),
            ]);
        }

        return $task->refresh();
    }

    /**
     * Anyone previously offered this task is no longer on the hook for it.
     *
     * @return Collection<int, TaskAssignment>
     */
    protected function withdrawPendingOffers(Task $task): Collection
    {
        $pending = $task->assignments()
            ->with('employee.user')
            ->where('status', AssignmentStatus::Pending)
            ->get();

        if ($pending->isNotEmpty()) {
            $task->assignments()
                ->where('status', AssignmentStatus::Pending)
                ->update([
                    'status' => AssignmentStatus::Reassigned,
                    'responded_at' => now(),
                ]);
        }

        return $pending;
    }

    protected function offerFor(Task $task, Employee $employee): TaskAssignment
    {
        $offer = $task->assignments()
            ->with('assignedBy')
            ->where('employee_id', $employee->id)
            ->where('status', AssignmentStatus::Pending)
            ->latest('id')
            ->first();

        if ($offer === null || $task->status !== TaskStatus::Assigned) {
            throw TaskWorkflowException::notOnOffer();
        }

        return $offer;
    }
}
