<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Enums\UserType;
use App\Modules\TaskManagement\Enums\PersonalTodoStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\PersonalTodo;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskReminder;
use Illuminate\Support\Facades\DB;

class TaskReminderService
{
    public function __construct(protected TaskNotifier $notifier) {}

    /**
     * Send all due reminders once. Idempotent via sent_at.
     */
    public function sendDueReminders(): int
    {
        $sent = 0;

        TaskReminder::query()
            ->whereNull('sent_at')
            ->where('remind_at', '<=', now())
            ->orderBy('id')
            ->chunkById(50, function ($reminders) use (&$sent) {
                foreach ($reminders as $reminder) {
                    if ($this->processReminder($reminder)) {
                        $sent++;
                    }
                }
            });

        PersonalTodo::query()
            ->where('status', PersonalTodoStatus::Pending)
            ->whereNotNull('reminder_at')
            ->whereNull('reminder_sent_at')
            ->where('reminder_at', '<=', now())
            ->orderBy('id')
            ->chunkById(50, function ($todos) use (&$sent) {
                foreach ($todos as $todo) {
                    if ($this->processPersonalTodoReminder($todo)) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }

    protected function processReminder(TaskReminder $reminder): bool
    {
        return DB::transaction(function () use ($reminder) {
            $locked = TaskReminder::query()->whereKey($reminder->id)->lockForUpdate()->first();

            if ($locked === null || $locked->sent_at !== null) {
                return false;
            }

            $task = Task::query()->find($locked->tm_task_id);

            if ($task === null || $task->status === TaskStatus::Completed) {
                $locked->update(['sent_at' => now()]);

                return false;
            }

            $recipient = $locked->recipient;

            if ($recipient === null || ! $recipient->is_active || $recipient->user_type !== UserType::Internal) {
                $locked->update(['sent_at' => now()]);

                return false;
            }

            $this->notifier->taskReminderDue($task, $locked);
            $locked->update(['sent_at' => now()]);

            return true;
        });
    }

    protected function processPersonalTodoReminder(PersonalTodo $todo): bool
    {
        return DB::transaction(function () use ($todo) {
            $locked = PersonalTodo::query()->whereKey($todo->id)->lockForUpdate()->first();

            if ($locked === null || $locked->reminder_sent_at !== null || $locked->status->isCompleted()) {
                return false;
            }

            if ($locked->reminder_at === null || $locked->reminder_at->isFuture()) {
                return false;
            }

            $recipient = $locked->user;

            if (! $recipient->is_active || $recipient->user_type !== UserType::Internal) {
                $locked->update(['reminder_sent_at' => now()]);

                return false;
            }

            $this->notifier->personalTodoReminderDue($locked);
            $locked->update(['reminder_sent_at' => now()]);

            return true;
        });
    }
}
