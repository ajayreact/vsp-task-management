<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Enums\UserType;
use App\Modules\TaskManagement\Enums\TaskStatus;
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
}
