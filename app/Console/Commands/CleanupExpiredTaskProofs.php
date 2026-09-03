<?php

namespace App\Console\Commands;

use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Illuminate\Console\Command;

class CleanupExpiredTaskProofs extends Command
{
    protected $signature = 'tasks:cleanup-expired-proofs';

    protected $description = 'Legacy alias for files:cleanup (Task Management temporary file retention)';

    public function handle(TaskManagementRetentionService $retention): int
    {
        return $this->call('files:cleanup');
    }
}
