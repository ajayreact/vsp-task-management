<?php

namespace App\Console\Commands;

use App\Modules\TaskManagement\Services\RecurringTaskService;
use Illuminate\Console\Command;

class ProcessRecurringTasks extends Command
{
    protected $signature = 'tasks:process-recurring';

    protected $description = 'Generate pending recurring Task Management occurrences idempotently';

    public function handle(RecurringTaskService $recurring): int
    {
        $generated = $recurring->processPendingGenerations();

        $this->info("Generated {$generated} recurring task occurrence(s).");

        return self::SUCCESS;
    }
}
