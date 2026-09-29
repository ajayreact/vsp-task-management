<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskEventType;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskStatus;
use App\Modules\RecruiterOperations\Exceptions\RecruiterTaskWorkflowException;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\RecruiterOperations\Models\RecruiterTaskEvent;
use Illuminate\Support\Facades\DB;

/**
 * Every change to who holds a recruiter task and what state it is in goes
 * through here, so the rules exist once rather than in each controller.
 *
 * Each move runs in a transaction on a row locked for update, validates the
 * transition, writes exactly one ro_task_events row (two when a task is first
 * opened: created, then assigned) and notifies only once the transaction has
 * committed.
 *
 * Assignee-only moves compare the actor's own employee record with the task's
 * assignee. That is a domain rule, not a permission, so Super Admin's
 * Gate::before bypass does not let anyone act as a recruiter they are not.
 */
class RecruiterTaskWorkflow
{
    public function __construct(
        protected RecruiterNotifier $notifier,
        protected RecruiterDirectory $directory,
    ) {}

    /**
     * Persist a new task already assigned to a recruiter.
     */
    public function open(RecruiterTask $task, Employee $assignee, User $actor): RecruiterTask
    {
        $task = DB::transaction(function () use ($task, $assignee, $actor) {
            if (! $this->directory->isAssignable($assignee)) {
                throw RecruiterTaskWorkflowException::notAssignableRecruiter();
            }

            $task->forceFill([
                'status' => RecruiterTaskStatus::Assigned,
                'assigned_employee_id' => $assignee->id,
                'created_by_user_id' => $actor->id,
            ])->save();

            $this->record($task, RecruiterTaskEventType::Created, $actor, [
                'to_status' => RecruiterTaskStatus::Assigned,
            ]);

            $this->record($task, RecruiterTaskEventType::Assigned, $actor, [
                'to_employee_id' => $assignee->id,
            ]);

            return $task;
        });

        DB::afterCommit(fn () => $this->notifier->taskAssigned($task, $actor));

        return $task;
    }

    public function accept(RecruiterTask $task, User $actor): RecruiterTask
    {
        $task = DB::transaction(function () use ($task, $actor) {
            $fresh = $this->lock($task);
            $this->guardAssignee($fresh, $actor);
            $from = $this->guard($fresh, RecruiterTaskStatus::InProgress, requiredFrom: RecruiterTaskStatus::Assigned);

            $fresh->forceFill([
                'status' => RecruiterTaskStatus::InProgress,
                'accepted_at' => now(),
                'started_at' => $fresh->started_at ?? now(),
            ])->save();

            $this->record($fresh, RecruiterTaskEventType::Accepted, $actor, [
                'from_status' => $from,
                'to_status' => RecruiterTaskStatus::InProgress,
            ]);

            return $fresh;
        });

        DB::afterCommit(fn () => $this->notifier->taskAccepted($task, $actor));

        return $task;
    }

    /**
     * The task stays with the recruiter who declined it until a manager
     * reassigns or cancels it.
     */
    public function decline(RecruiterTask $task, User $actor, string $reason): RecruiterTask
    {
        $task = DB::transaction(function () use ($task, $actor, $reason) {
            $fresh = $this->lock($task);
            $this->guardAssignee($fresh, $actor);
            $from = $this->guard($fresh, RecruiterTaskStatus::Declined);

            $fresh->forceFill(['status' => RecruiterTaskStatus::Declined])->save();

            $this->record($fresh, RecruiterTaskEventType::Declined, $actor, [
                'from_status' => $from,
                'to_status' => RecruiterTaskStatus::Declined,
                'reason' => $reason,
            ]);

            return $fresh;
        });

        DB::afterCommit(fn () => $this->notifier->taskDeclined($task, $actor, $reason));

        return $task;
    }

    public function hold(RecruiterTask $task, User $actor, string $reason): RecruiterTask
    {
        $task = DB::transaction(function () use ($task, $actor, $reason) {
            $fresh = $this->lock($task);
            $from = $this->guard($fresh, RecruiterTaskStatus::OnHold);

            $fresh->forceFill(['status' => RecruiterTaskStatus::OnHold])->save();

            $this->record($fresh, RecruiterTaskEventType::PutOnHold, $actor, [
                'from_status' => $from,
                'to_status' => RecruiterTaskStatus::OnHold,
                'reason' => $reason,
            ]);

            return $fresh;
        });

        DB::afterCommit(fn () => $this->notifier->taskPutOnHold($task, $actor, $reason));

        return $task;
    }

    public function resume(RecruiterTask $task, User $actor): RecruiterTask
    {
        $task = DB::transaction(function () use ($task, $actor) {
            $fresh = $this->lock($task);
            $from = $this->guard($fresh, RecruiterTaskStatus::InProgress, requiredFrom: RecruiterTaskStatus::OnHold);

            $fresh->forceFill(['status' => RecruiterTaskStatus::InProgress])->save();

            $this->record($fresh, RecruiterTaskEventType::Resumed, $actor, [
                'from_status' => $from,
                'to_status' => RecruiterTaskStatus::InProgress,
            ]);

            return $fresh;
        });

        DB::afterCommit(fn () => $this->notifier->taskResumed($task, $actor));

        return $task;
    }

    /**
     * Records how much of the target was reached. The count is a number the
     * recruiter reports; it creates no candidate records.
     */
    public function complete(RecruiterTask $task, User $actor, ?int $achievedCount, ?string $completionNote): RecruiterTask
    {
        $task = DB::transaction(function () use ($task, $actor, $achievedCount, $completionNote) {
            $fresh = $this->lock($task);
            $this->guardAssignee($fresh, $actor);
            $from = $this->guard($fresh, RecruiterTaskStatus::Completed);

            if ($achievedCount !== null && $achievedCount < 0) {
                throw new RecruiterTaskWorkflowException('The achieved count cannot be negative.');
            }

            $fresh->forceFill([
                'status' => RecruiterTaskStatus::Completed,
                'achieved_count' => $achievedCount,
                'completion_note' => $completionNote,
                'completed_at' => now(),
            ])->save();

            $this->record($fresh, RecruiterTaskEventType::Completed, $actor, [
                'from_status' => $from,
                'to_status' => RecruiterTaskStatus::Completed,
                'metadata' => [
                    'target_count' => $fresh->target_count,
                    'achieved_count' => $achievedCount,
                    'completion_note' => $completionNote,
                ],
            ]);

            return $fresh;
        });

        DB::afterCommit(fn () => $this->notifier->taskCompleted($task, $actor));

        return $task;
    }

    /**
     * A manager sends completed work back. "Reopened" is the event; the status
     * returns to in progress.
     */
    public function reopen(RecruiterTask $task, User $actor, string $reason): RecruiterTask
    {
        $task = DB::transaction(function () use ($task, $actor, $reason) {
            $fresh = $this->lock($task);
            $from = $this->guard($fresh, RecruiterTaskStatus::InProgress, requiredFrom: RecruiterTaskStatus::Completed);

            $fresh->forceFill([
                'status' => RecruiterTaskStatus::InProgress,
                'completed_at' => null,
            ])->save();

            $this->record($fresh, RecruiterTaskEventType::Reopened, $actor, [
                'from_status' => $from,
                'to_status' => RecruiterTaskStatus::InProgress,
                'reason' => $reason,
            ]);

            return $fresh;
        });

        DB::afterCommit(fn () => $this->notifier->taskReopened($task, $actor, $reason));

        return $task;
    }

    public function cancel(RecruiterTask $task, User $actor, string $reason): RecruiterTask
    {
        $task = DB::transaction(function () use ($task, $actor, $reason) {
            $fresh = $this->lock($task);
            $from = $this->guard($fresh, RecruiterTaskStatus::Cancelled);

            $fresh->forceFill([
                'status' => RecruiterTaskStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();

            $this->record($fresh, RecruiterTaskEventType::Cancelled, $actor, [
                'from_status' => $from,
                'to_status' => RecruiterTaskStatus::Cancelled,
                'reason' => $reason,
            ]);

            return $fresh;
        });

        DB::afterCommit(fn () => $this->notifier->taskCancelled($task, $actor, $reason));

        return $task;
    }

    /**
     * Hand an unstarted task to a recruiter: a declined task goes back to
     * assigned, and a pending one can move to someone else.
     */
    public function reassign(RecruiterTask $task, Employee $recruiter, User $actor, ?string $reason = null): RecruiterTask
    {
        $previousEmployeeId = null;

        $task = DB::transaction(function () use ($task, $recruiter, $actor, $reason, &$previousEmployeeId) {
            $fresh = $this->lock($task);

            if ($fresh->status->isTerminal()) {
                throw RecruiterTaskWorkflowException::cancelled();
            }

            if (! $fresh->status->isReassignable()) {
                throw RecruiterTaskWorkflowException::notReassignable();
            }

            if ($fresh->status === RecruiterTaskStatus::Assigned && $fresh->assigned_employee_id === $recruiter->id) {
                throw RecruiterTaskWorkflowException::alreadyAssignedTo();
            }

            if (! $this->directory->isAssignable($recruiter)) {
                throw RecruiterTaskWorkflowException::notAssignableRecruiter();
            }

            $from = $fresh->status;
            $previousEmployeeId = $fresh->assigned_employee_id;

            $fresh->forceFill([
                'status' => RecruiterTaskStatus::Assigned,
                'assigned_employee_id' => $recruiter->id,
                'accepted_at' => null,
                'started_at' => null,
            ])->save();

            $this->record($fresh, RecruiterTaskEventType::Reassigned, $actor, [
                'from_status' => $from,
                'to_status' => RecruiterTaskStatus::Assigned,
                'from_employee_id' => $previousEmployeeId,
                'to_employee_id' => $recruiter->id,
                'reason' => $reason,
            ]);

            return $fresh;
        });

        DB::afterCommit(fn () => $this->notifier->taskReassigned($task, $previousEmployeeId, $actor));

        return $task;
    }

    protected function lock(RecruiterTask $task): RecruiterTask
    {
        return RecruiterTask::query()->whereKey($task->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * @return RecruiterTaskStatus the status being left
     */
    protected function guard(RecruiterTask $task, RecruiterTaskStatus $target, ?RecruiterTaskStatus $requiredFrom = null): RecruiterTaskStatus
    {
        $from = $task->status;

        if ($from->isTerminal()) {
            throw RecruiterTaskWorkflowException::cancelled();
        }

        if (($requiredFrom !== null && $from !== $requiredFrom) || ! $from->canTransitionTo($target)) {
            throw RecruiterTaskWorkflowException::cannotTransition($from, $target);
        }

        return $from;
    }

    protected function guardAssignee(RecruiterTask $task, User $actor): void
    {
        $employee = Employee::query()->where('user_id', $actor->id)->first();

        if (! $task->isAssignedTo($employee)) {
            throw RecruiterTaskWorkflowException::notAssignee();
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function record(RecruiterTask $task, RecruiterTaskEventType $event, User $actor, array $attributes = []): RecruiterTaskEvent
    {
        return $task->events()->create([
            'event' => $event,
            'actor_user_id' => $actor->id,
            'occurred_at' => now(),
            ...$attributes,
        ]);
    }
}
