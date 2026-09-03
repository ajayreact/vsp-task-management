<?php

namespace App\Console\Commands;

use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Illuminate\Console\Command;

class CleanupTemporaryFiles extends Command
{
    protected $signature = 'files:cleanup';

    protected $description = 'Delete expired Task Management working files and creative review files according to retention policy';

    public function handle(TaskManagementRetentionService $retention): int
    {
        $policy = $retention->policy();

        if (! $policy['enabled'] || $policy['days'] === null) {
            $this->info('Automatic file retention is disabled. No temporary files were deleted.');

            return self::SUCCESS;
        }

        $result = $retention->runCleanup();

        $this->info(sprintf(
            'Deleted %d temporary file(s). Missing physical files: %d. Skipped: %d.',
            $result['deleted'],
            $result['missing_files'],
            $result['skipped'],
        ));

        return self::SUCCESS;
    }
}
