<?php

namespace App\Modules\RecruiterOperations\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\RecruiterOperations\Enums\RecruiterTaskEventType;
use App\Modules\RecruiterOperations\Models\AssessmentAssignment;
use App\Modules\RecruiterOperations\Models\AssessmentAttempt;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\RecruiterOperations\Models\TrainingAssignment;
use App\Modules\RecruiterOperations\Notifications\RecruiterOperationsNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Resolves recipients and delivers recruiter.* notifications. Never notifies
 * the actor, and skips inactive or non-internal accounts.
 *
 * RecruiterTaskWorkflow, TrainingAssignmentService and the assessment
 * services call these only after their transaction commits.
 */
class RecruiterNotifier
{
    public function taskAssigned(RecruiterTask $task, User $actor): void
    {
        $recipient = $this->assigneeUser($task);

        if ($recipient !== null) {
            $this->send($recipient, $actor, $this->payload($task, 'recruiter.task.assigned', 'New recruiter task',
                "You have been assigned \"{$task->title}\"."));
        }
    }

    public function taskReassigned(RecruiterTask $task, ?int $previousEmployeeId, User $actor): void
    {
        if ($previousEmployeeId !== null && $previousEmployeeId !== $task->assigned_employee_id) {
            $previous = Employee::query()->with('user')->find($previousEmployeeId)?->user;

            if ($previous !== null) {
                $this->send($previous, $actor, $this->payload($task, 'recruiter.task.reassigned_away', 'Recruiter task reassigned',
                    "\"{$task->title}\" is no longer assigned to you."));
            }
        }

        $this->taskAssigned($task, $actor);
    }

    public function taskAccepted(RecruiterTask $task, User $actor): void
    {
        foreach ($this->overseers($task) as $recipient) {
            $this->send($recipient, $actor, $this->payload($task, 'recruiter.task.accepted', 'Recruiter task accepted',
                "{$actor->name} accepted \"{$task->title}\"."));
        }
    }

    public function taskDeclined(RecruiterTask $task, User $actor, string $reason): void
    {
        foreach ($this->overseers($task) as $recipient) {
            $this->send($recipient, $actor, $this->payload($task, 'recruiter.task.declined', 'Recruiter task declined',
                "{$actor->name} declined \"{$task->title}\". Reason: {$reason}"));
        }
    }

    public function taskPutOnHold(RecruiterTask $task, User $actor, string $reason): void
    {
        foreach ($this->stakeholders($task) as $recipient) {
            $this->send($recipient, $actor, $this->payload($task, 'recruiter.task.put_on_hold', 'Recruiter task on hold',
                "{$actor->name} put \"{$task->title}\" on hold. Reason: {$reason}"));
        }
    }

    public function taskResumed(RecruiterTask $task, User $actor): void
    {
        foreach ($this->stakeholders($task) as $recipient) {
            $this->send($recipient, $actor, $this->payload($task, 'recruiter.task.resumed', 'Recruiter task resumed',
                "{$actor->name} resumed \"{$task->title}\"."));
        }
    }

    public function taskCompleted(RecruiterTask $task, User $actor): void
    {
        foreach ($this->overseers($task) as $recipient) {
            $this->send($recipient, $actor, $this->payload($task, 'recruiter.task.completed', 'Recruiter task completed',
                "{$actor->name} completed \"{$task->title}\"."));
        }
    }

    public function taskReopened(RecruiterTask $task, User $actor, string $reason): void
    {
        $recipient = $this->assigneeUser($task);

        if ($recipient !== null) {
            $this->send($recipient, $actor, $this->payload($task, 'recruiter.task.reopened', 'Recruiter task reopened',
                "{$actor->name} reopened \"{$task->title}\". Reason: {$reason}"));
        }
    }

    public function taskCancelled(RecruiterTask $task, User $actor, string $reason): void
    {
        foreach ($this->stakeholders($task) as $recipient) {
            $this->send($recipient, $actor, $this->payload($task, 'recruiter.task.cancelled', 'Recruiter task cancelled',
                "{$actor->name} cancelled \"{$task->title}\". Reason: {$reason}"));
        }
    }

    public function trainingAssigned(TrainingAssignment $assignment, User $actor): void
    {
        $assignment->loadMissing(['version.course', 'employee.user']);
        $recipient = $assignment->employee->user;
        $course = $assignment->version->course;
        $due = $assignment->due_at !== null ? ' Due '.$assignment->due_at->format('j M Y').'.' : '';

        $this->send($recipient, $actor, [
            'event' => 'recruiter.training.assigned',
            'title' => 'New training assigned',
            'body' => "You have been assigned the course \"{$course->title}\".{$due}",
            'url' => "/recruiter/training/courses/{$course->id}",
            'recruiter_training_assignment_id' => $assignment->id,
        ]);
    }

    public function assessmentAssigned(AssessmentAssignment $assignment, User $actor): void
    {
        $assignment->loadMissing(['version.assessment', 'employee.user']);
        $assessment = $assignment->version->assessment;
        $due = $assignment->due_at !== null ? ' Due '.$assignment->due_at->format('j M Y').'.' : '';

        $this->send($assignment->employee->user, $actor, [
            'event' => 'recruiter.assessment.assigned',
            'title' => 'New assessment assigned',
            'body' => "You have been assigned the quiz \"{$assessment->title}\".{$due}",
            'url' => "/recruiter/assessments/{$assignment->id}",
            'recruiter_assessment_assignment_id' => $assignment->id,
        ]);
    }

    /**
     * Sent when a reviewed attempt gets its final result.
     */
    public function assessmentResult(AssessmentAttempt $attempt, User $actor): void
    {
        $attempt->loadMissing(['version.assessment', 'employee.user']);
        $assessment = $attempt->version->assessment;

        $this->send($attempt->employee->user, $actor, [
            'event' => 'recruiter.assessment.result',
            'title' => 'Assessment result available',
            'body' => "Your attempt at \"{$assessment->title}\" has been reviewed.",
            'url' => "/recruiter/assessments/attempts/{$attempt->id}",
            'recruiter_assessment_attempt_id' => $attempt->id,
        ]);
    }

    /**
     * @return array{event: string, title: string, body: string, url: string, recruiter_task_id: int}
     */
    protected function payload(RecruiterTask $task, string $event, string $title, string $body): array
    {
        return [
            'event' => $event,
            'title' => $title,
            'body' => $body,
            'url' => "/recruiter/tasks/{$task->id}",
            'recruiter_task_id' => $task->id,
        ];
    }

    protected function assigneeUser(RecruiterTask $task): ?User
    {
        if ($task->assigned_employee_id === null) {
            return null;
        }

        return Employee::query()->with('user')->find($task->assigned_employee_id)?->user;
    }

    /**
     * The people answerable for the task: whoever created it and whoever made
     * the current assignment.
     *
     * @return Collection<int, User>
     */
    protected function overseers(RecruiterTask $task): Collection
    {
        $assignerId = $task->events()
            ->whereIn('event', [RecruiterTaskEventType::Assigned->value, RecruiterTaskEventType::Reassigned->value])
            ->latest('occurred_at')
            ->latest('id')
            ->value('actor_user_id');

        return User::query()
            ->whereIn('id', array_filter([$task->created_by_user_id, $assignerId]))
            ->get();
    }

    /**
     * Overseers plus the recruiter holding the task.
     *
     * @return Collection<int, User>
     */
    protected function stakeholders(RecruiterTask $task): Collection
    {
        return $this->overseers($task)
            ->push($this->assigneeUser($task))
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * @param  array{event: string, title: string, body: string, url: string}&array<string, mixed>  $payload
     */
    protected function send(User $recipient, User $actor, array $payload): void
    {
        if ($recipient->id === $actor->id) {
            return;
        }

        if (! $recipient->is_active || ! $recipient->isInternal()) {
            return;
        }

        $payload['actor'] = $this->actorSummary($actor);

        $notification = new RecruiterOperationsNotification($payload);

        try {
            Notification::sendNow($recipient, $notification, ['database']);
        } catch (\Throwable $exception) {
            report($exception);
            Log::warning('Recruiter notification database delivery failed.', [
                'recipient_id' => $recipient->id,
                'event' => $payload['event'],
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        $storedId = $recipient->notifications()->latest()->value('id');

        if (is_string($storedId)) {
            $notification->id = $storedId;
        }

        if (! $this->shouldBroadcast()) {
            return;
        }

        try {
            Notification::sendNow($recipient, $notification, ['broadcast']);
        } catch (\Throwable $exception) {
            report($exception);
            Log::warning('Recruiter notification broadcast failed.', [
                'recipient_id' => $recipient->id,
                'event' => $payload['event'],
                'error' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @return array{id: int, name: string, avatar: string|null}
     */
    protected function actorSummary(User $actor): array
    {
        $actor->loadMissing('employee.media');

        return [
            'id' => $actor->id,
            'name' => $actor->name,
            'avatar' => $actor->employee?->getFirstMediaUrl('avatar', 'thumb') ?: null,
        ];
    }

    protected function shouldBroadcast(): bool
    {
        $driver = config('broadcasting.default');

        return filled($driver) && $driver !== 'null';
    }
}
