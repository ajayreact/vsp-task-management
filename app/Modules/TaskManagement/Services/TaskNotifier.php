<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Enums\UserType;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\ReviewDecision;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskAssignment;
use App\Modules\TaskManagement\Models\TaskReminder;
use App\Modules\TaskManagement\Models\TaskSubtask;
use App\Modules\TaskManagement\Models\Timesheet;
use App\Modules\TaskManagement\Notifications\StaffDatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Resolves recipients and writes database notifications for Task Management
 * workflow events. Never notifies the actor.
 *
 * Callers invoke these methods after their own DB transaction succeeds.
 */
class TaskNotifier
{
    public function taskAssigned(Task $task, Employee $assignee, User $actor, Collection $withdrawnAssignments): void
    {
        $assignee->loadMissing('user');

        foreach ($withdrawnAssignments as $assignment) {
            /** @var TaskAssignment $assignment */
            $previous = $assignment->employee?->user;
            if ($previous !== null && $previous->id !== $assignee->user_id) {
                $this->send($previous, $actor, [
                    'event' => 'task.reassigned_away',
                    'title' => 'Task reassigned',
                    'body' => "\"{$task->title}\" is no longer assigned to you.",
                    'url' => "/tasks/{$task->id}",
                    'task_id' => $task->id,
                ]);
            }
        }

        if ($assignee->user !== null) {
            $this->send($assignee->user, $actor, $this->taskAssignedPayload($task));
        }
    }

    public function taskPublished(Task $task, User $actor, Collection $withdrawnAssignments): void
    {
        foreach ($withdrawnAssignments as $assignment) {
            /** @var TaskAssignment $assignment */
            $previous = $assignment->employee?->user;
            if ($previous !== null) {
                $this->send($previous, $actor, [
                    'event' => 'task.reassigned_away',
                    'title' => 'Task moved to Open Board',
                    'body' => "\"{$task->title}\" was published to the Open Board and is no longer assigned to you.",
                    'url' => '/tasks/board',
                    'task_id' => $task->id,
                ]);
            }
        }

        $task->loadMissing('creator');
        if ($task->creator && $task->creator->id !== $actor->id) {
            $this->send($task->creator, $actor, [
                'event' => 'task.published',
                'title' => 'Task published to Open Board',
                'body' => "{$actor->name} published \"{$task->title}\" to the Open Board.",
                'url' => "/tasks/{$task->id}",
                'task_id' => $task->id,
            ]);
        }

        $this->notifyOpenBoardAvailable($task, $actor);
    }

    public function notifyOpenBoardAvailable(Task $task, User $actor): void
    {
        foreach ($this->eligibleOpenBoardEmployeesFor($task) as $recipient) {
            /** @var User $recipient */
            if ($recipient->id === $actor->id) {
                continue;
            }

            $this->send($recipient, $actor, [
                'event' => 'task.open_available',
                'title' => 'New Open Task Available',
                'body' => "A new task has been posted on the Open Board: \"{$task->title}\".",
                'url' => "/tasks/{$task->id}",
                'task_id' => $task->id,
            ]);
        }
    }

    public function taskAccepted(Task $task, User $actor, ?User $assigner): void
    {
        $task->loadMissing('creator');
        $recipients = collect([$task->creator, $assigner])->filter()->unique('id');

        foreach ($recipients as $recipient) {
            /** @var User $recipient */
            $this->send($recipient, $actor, [
                'event' => 'task.accepted',
                'title' => 'Task accepted',
                'body' => "{$actor->name} accepted \"{$task->title}\".",
                'url' => "/tasks/{$task->id}",
                'task_id' => $task->id,
            ]);
        }
    }

    public function taskDeclined(Task $task, User $actor, ?User $assigner, ?string $reason): void
    {
        $task->loadMissing('creator');
        $recipients = collect([$task->creator, $assigner])->filter()->unique('id');
        $suffix = $reason ? " Reason: {$reason}" : '';

        foreach ($recipients as $recipient) {
            /** @var User $recipient */
            $this->send($recipient, $actor, [
                'event' => 'task.declined',
                'title' => 'Task declined',
                'body' => "{$actor->name} declined \"{$task->title}\" and it returned to the Open Board.{$suffix}",
                'url' => "/tasks/{$task->id}",
                'task_id' => $task->id,
            ]);
        }
    }

    public function taskClaimed(Task $task, User $actor): void
    {
        foreach ($this->taskClaimStakeholders($task, $actor) as $recipient) {
            /** @var User $recipient */
            $this->send($recipient, $actor, [
                'event' => 'task.claimed',
                'title' => 'Task claimed',
                'body' => "{$actor->name} claimed \"{$task->title}\" from the Open Board.",
                'url' => "/tasks/{$task->id}",
                'task_id' => $task->id,
            ]);
        }
    }

    public function taskInProgress(Task $task, User $actor): void
    {
        foreach ($this->taskOversightUsers($task) as $recipient) {
            /** @var User $recipient */
            $this->send($recipient, $actor, [
                'event' => 'task.in_progress',
                'title' => 'Task in progress',
                'body' => "{$actor->name} started work on \"{$task->title}\".",
                'url' => "/tasks/{$task->id}",
                'task_id' => $task->id,
            ]);
        }
    }

    public function taskCompleted(Task $task, User $actor): void
    {
        $task->loadMissing('assignee.user');

        $recipients = $this->taskOversightUsers($task)
            ->push($task->assignee?->user)
            ->filter()
            ->unique('id');

        foreach ($recipients as $recipient) {
            /** @var User $recipient */
            $this->send($recipient, $actor, [
                'event' => 'task.completed',
                'title' => 'Task completed',
                'body' => "{$actor->name} completed \"{$task->title}\".",
                'url' => "/tasks/{$task->id}",
                'task_id' => $task->id,
            ]);
        }
    }

    public function proofSubmitted(Task $task, User $actor): void
    {
        foreach ($this->usersWhoCan(Ability::ReviewDeliverables) as $reviewer) {
            $this->send($reviewer, $actor, [
                'event' => 'task.proof_submitted',
                'title' => 'Proof ready for review',
                'body' => "{$actor->name} submitted a proof for \"{$task->title}\".",
                'url' => "/tasks/{$task->id}",
                'task_id' => $task->id,
            ]);
        }
    }

    public function proofDecided(Task $task, User $actor, ReviewDecision $decision): void
    {
        $task->loadMissing('assignee.user');
        $assigneeUser = $task->assignee?->user;
        if ($assigneeUser === null) {
            return;
        }

        [$title, $body] = match ($decision) {
            ReviewDecision::Approve => [
                'Work approved — share with client',
                "{$actor->name} approved your proof for \"{$task->title}\". A client share link is ready.",
            ],
            ReviewDecision::RequestChanges => [
                'Changes requested',
                "{$actor->name} requested changes on \"{$task->title}\".",
            ],
            ReviewDecision::Reject => [
                'Proof rejected',
                "{$actor->name} rejected the proof for \"{$task->title}\". Please revise and resubmit.",
            ],
        };

        $this->send($assigneeUser, $actor, [
            'event' => 'task.proof_'.$decision->value,
            'title' => $title,
            'body' => $body,
            'url' => "/tasks/{$task->id}",
            'task_id' => $task->id,
        ]);
    }

    public function timesheetSubmitted(Timesheet $timesheet, User $actor): void
    {
        $timesheet->loadMissing('employee.user');
        $label = $timesheet->period_start->toDateString().' – '.$timesheet->period_end->toDateString();

        foreach ($this->usersWhoCan(Ability::ApproveTimesheets) as $approver) {
            $this->send($approver, $actor, [
                'event' => 'timesheet.submitted',
                'title' => 'Timesheet submitted',
                'body' => "{$actor->name} submitted a timesheet ({$label}).",
                'url' => "/tasks/timesheets/{$timesheet->id}",
                'timesheet_id' => $timesheet->id,
            ]);
        }
    }

    public function timesheetReviewed(Timesheet $timesheet, User $actor, TimesheetStatus $status): void
    {
        $timesheet->loadMissing('employee.user');
        $owner = $timesheet->employee->user;
        if ($owner === null) {
            return;
        }

        $label = $timesheet->period_start->toDateString().' – '.$timesheet->period_end->toDateString();
        $approved = $status === TimesheetStatus::Approved;

        $this->send($owner, $actor, [
            'event' => $approved ? 'timesheet.approved' : 'timesheet.rejected',
            'title' => $approved ? 'Timesheet approved' : 'Timesheet rejected',
            'body' => $approved
                ? "{$actor->name} approved your timesheet ({$label})."
                : "{$actor->name} rejected your timesheet ({$label}).",
            'url' => "/tasks/timesheets/{$timesheet->id}",
            'timesheet_id' => $timesheet->id,
        ]);
    }

    public function taskCommented(Task $task, User $actor): void
    {
        $task->loadMissing('assignee.user');
        $assigneeUser = $task->assignee?->user;

        if ($assigneeUser === null) {
            return;
        }

        $preview = "{$actor->name} commented on \"{$task->title}\".";

        $this->send($assigneeUser, $actor, [
            'event' => 'task.comment',
            'title' => 'New task comment',
            'body' => $preview,
            'url' => "/tasks/{$task->id}",
            'task_id' => $task->id,
        ]);
    }

    public function taskReminderDue(Task $task, TaskReminder $reminder): void
    {
        $reminder->loadMissing('recipient');
        $recipient = $reminder->recipient;

        if ($recipient === null || ! $recipient->is_active || $recipient->user_type !== UserType::Internal) {
            return;
        }

        $body = $reminder->message ?: "Reminder for \"{$task->title}\".";

        $this->deliver($recipient, [
            'event' => 'task.reminder',
            'title' => 'Task reminder',
            'body' => $body,
            'url' => "/tasks/{$task->id}",
            'task_id' => $task->id,
        ]);
    }

    /**
     * @return array{event: string, title: string, body: string, url: string, task_id: int}
     */
    public function taskAssignedPayload(Task $task): array
    {
        return [
            'event' => 'task.assigned',
            'title' => 'New Task Assigned',
            'body' => "You have been assigned to the task: {$task->title}",
            'url' => "/tasks/{$task->id}",
            'task_id' => $task->id,
        ];
    }

    public function clientApproved(Task $task): void
    {
        $task->loadMissing('assignee.user');

        $recipients = $this->taskOversightUsers($task)
            ->push($task->assignee?->user)
            ->filter()
            ->unique('id');

        foreach ($recipients as $recipient) {
            /** @var User $recipient */
            $this->deliver($recipient, [
                'event' => 'task.client_approved',
                'title' => 'Client approved work',
                'body' => "The client approved \"{$task->title}\". The task is now completed.",
                'url' => "/tasks/{$task->id}",
                'task_id' => $task->id,
            ]);
        }
    }

    public function clientRequestedChanges(Task $task, ?string $feedback): void
    {
        $task->loadMissing('assignee.user');
        $assigneeUser = $task->assignee?->user;

        if ($assigneeUser === null) {
            return;
        }

        $suffix = $feedback ? " Feedback: {$feedback}" : '';

        $this->deliver($assigneeUser, [
            'event' => 'task.client_request_changes',
            'title' => 'Client requested changes',
            'body' => "The client requested changes on \"{$task->title}\".{$suffix}",
            'url' => "/tasks/{$task->id}",
            'task_id' => $task->id,
        ]);
    }

    public function subtaskAssigned(Task $task, TaskSubtask $subtask, User $actor): void
    {
        $subtask->loadMissing('assignee.user');
        $assigneeUser = $subtask->assignee?->user;

        if ($assigneeUser === null) {
            return;
        }

        $this->send($assigneeUser, $actor, [
            'event' => 'task.subtask_assigned',
            'title' => 'Subtask assigned',
            'body' => "{$actor->name} assigned you \"{$subtask->title}\" on \"{$task->title}\".",
            'url' => "/tasks/{$task->id}",
            'task_id' => $task->id,
        ]);
    }

    public function subtaskUpdated(Task $task, TaskSubtask $subtask, User $actor): void
    {
        $subtask->loadMissing('assignee.user');
        $assigneeUser = $subtask->assignee?->user;

        if ($assigneeUser === null) {
            return;
        }

        $this->send($assigneeUser, $actor, [
            'event' => 'task.subtask_updated',
            'title' => 'Subtask updated',
            'body' => "\"{$subtask->title}\" on \"{$task->title}\" was updated.",
            'url' => "/tasks/{$task->id}",
            'task_id' => $task->id,
        ]);
    }

    /**
     * Task creator and project manager — people who oversee delivery.
     *
     * @return Collection<int, User>
     */
    protected function taskOversightUsers(Task $task): Collection
    {
        $task->loadMissing(['creator', 'project.manager.user']);

        return collect([
            $task->creator,
            $task->project->manager?->user,
        ])->filter()->unique('id')->values();
    }

    /**
     * Oversight plus the claimer's reporting manager when an Open Board task is taken.
     *
     * @return Collection<int, User>
     */
    protected function taskClaimStakeholders(Task $task, User $actor): Collection
    {
        $actor->loadMissing('employee.manager.user');

        return $this->taskOversightUsers($task)
            ->push($actor->employee?->manager?->user)
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * @param  array{event: string, title: string, body: string, url: string, task_id?: int|null, timesheet_id?: int|null}  $payload
     */
    protected function send(User $recipient, User $actor, array $payload): void
    {
        if ($recipient->id === $actor->id) {
            return;
        }

        if (! $recipient->is_active || $recipient->user_type !== UserType::Internal) {
            return;
        }

        $this->deliver($recipient, $payload);
    }

    /**
     * Persist to the database first, then attempt a live broadcast when configured.
     * Database delivery must succeed even if Reverb/WebSockets are unavailable.
     *
     * @param  array{event: string, title: string, body: string, url: string, task_id?: int|null, timesheet_id?: int|null}  $payload
     */
    protected function deliver(User $recipient, array $payload): void
    {
        if (! $recipient->is_active || $recipient->user_type !== UserType::Internal) {
            return;
        }

        $notification = new StaffDatabaseNotification($payload);
        $channels = ['database'];

        if ($this->shouldBroadcastNotifications()) {
            $channels[] = 'broadcast';
        }

        try {
            Notification::sendNow($recipient, $notification, $channels);
        } catch (\Throwable $exception) {
            report($exception);

            if ($recipient->notifications()->count() === 0) {
                Notification::sendNow($recipient, $notification, ['database']);
            }
        }
    }

    protected function shouldBroadcastNotifications(): bool
    {
        $driver = config('broadcasting.default');

        return filled($driver) && $driver !== 'null';
    }

    /**
     * Employees who may claim open-board work (matches TaskPolicy::claim).
     *
     * @return Collection<int, User>
     */
    public function eligibleOpenBoardEmployeesFor(Task $task): Collection
    {
        return User::query()
            ->internal()
            ->where('is_active', true)
            ->permission(Ability::AccessTasks->value)
            ->whereHas('employee', fn ($query) => $query->assignable())
            ->get()
            ->unique('id')
            ->values();
    }

    protected function eligibleOpenBoardEmployees(Task $task): Collection
    {
        return $this->eligibleOpenBoardEmployeesFor($task);
    }

    /**
     * @return Collection<int, User>
     */
    protected function usersWhoCan(Ability $ability): Collection
    {
        $withPermission = User::query()
            ->internal()
            ->where('is_active', true)
            ->permission($ability->value)
            ->get();

        $superAdmins = User::query()
            ->internal()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', SystemRole::SuperAdmin->value))
            ->get();

        return $withPermission->merge($superAdmins)->unique('id')->values();
    }
}
