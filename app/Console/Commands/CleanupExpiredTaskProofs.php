<?php

namespace App\Console\Commands;

use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Illuminate\Console\Command;

class CleanupExpiredTaskProofs extends Command
{
    protected $signature = 'tasks:cleanup-expired-proofs';

    protected $description = 'Delete expired Task Management deliverable proof files according to the retention policy';

    public function handle(TaskManagementRetentionService $retention): int
    {
        $policy = $retention->policy();

        if (! $policy['enabled'] || $policy['days'] === null) {
            $this->info('Automatic proof retention is disabled. No files were deleted.');

            return self::SUCCESS;
        }

        $cleaned = 0;

        foreach ($retention->eligibleDeliverables() as $deliverable) {
            $retention->cleanup($deliverable);
            $cleaned++;
        }

        $this->info("Cleaned proof files for {$cleaned} deliverable(s).");

        return self::SUCCESS;
    }
}
