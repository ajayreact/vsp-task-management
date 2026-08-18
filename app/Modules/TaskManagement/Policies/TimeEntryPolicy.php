<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\TimeEntry;

class TimeEntryPolicy
{
    public function delete(User $user, TimeEntry $entry): bool
    {
        if ($entry->employee_id !== $user->employee?->id) {
            return false;
        }

        $timesheet = $entry->timesheet;

        return $timesheet === null || ! $timesheet->status->isLocked();
    }
}
