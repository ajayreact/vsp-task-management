<?php

namespace App\Modules\TaskManagement\Policies;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use App\Modules\TaskManagement\Models\Timesheet;

class TimesheetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(Ability::AccessTasks->value);
    }

    public function view(User $user, Timesheet $timesheet): bool
    {
        return $this->owns($user, $timesheet) || $user->can(Ability::ApproveTimesheets->value);
    }

    public function submit(User $user, Timesheet $timesheet): bool
    {
        return $this->owns($user, $timesheet)
            && in_array($timesheet->status, [TimesheetStatus::Draft, TimesheetStatus::Rejected], true);
    }

    public function review(User $user, Timesheet $timesheet): bool
    {
        return $user->can(Ability::ApproveTimesheets->value)
            && $timesheet->status === TimesheetStatus::Submitted
            && ! $this->owns($user, $timesheet);
    }

    protected function owns(User $user, Timesheet $timesheet): bool
    {
        return $timesheet->employee_id === $user->employee?->id;
    }
}
