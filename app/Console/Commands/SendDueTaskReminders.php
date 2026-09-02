<?php

namespace App\Console\Commands;

use App\Modules\TaskManagement\Services\TaskReminderService;
use Illuminate\Console\Command;

class SendDueTaskReminders extends Command
{
    protected $signature = 'tasks:send-due-reminders';

    protected $description = 'Send due Task Management and personal todo reminders through the in-app notification system';

    public function handle(TaskReminderService $reminders): int
    {
        $sent = $reminders->sendDueReminders();

        $this->info("Sent {$sent} reminder(s).");

        return self::SUCCESS;
    }
}
