<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\Timesheet;
use Illuminate\Support\Facades\DB;

class TimesheetService
{
    public function __construct(protected TaskNotifier $notifier) {}

    public function submit(Timesheet $timesheet): Timesheet
    {
        $actor = $timesheet->employee()->with('user')->first()?->user;

        $timesheet = DB::transaction(function () use ($timesheet) {
            if (! in_array($timesheet->status, [TimesheetStatus::Draft, TimesheetStatus::Rejected], true)) {
                throw ProductivityException::timesheetNotSubmittable();
            }

            $timesheet->refreshTotal();

            $timesheet->update([
                'status' => TimesheetStatus::Submitted,
                'submitted_at' => now(),
                'approved_by_user_id' => null,
                'approved_at' => null,
                'review_note' => null,
            ]);

            return $timesheet->refresh();
        });

        if ($actor !== null) {
            $this->notifier->timesheetSubmitted($timesheet, $actor);
        }

        return $timesheet;
    }

    public function approve(Timesheet $timesheet, User $reviewer, ?string $note = null): Timesheet
    {
        return $this->decide($timesheet, $reviewer, TimesheetStatus::Approved, $note);
    }

    public function reject(Timesheet $timesheet, User $reviewer, ?string $note = null): Timesheet
    {
        return $this->decide($timesheet, $reviewer, TimesheetStatus::Rejected, $note);
    }

    protected function decide(Timesheet $timesheet, User $reviewer, TimesheetStatus $target, ?string $note): Timesheet
    {
        $timesheet = DB::transaction(function () use ($timesheet, $reviewer, $target, $note) {
            if ($timesheet->status !== TimesheetStatus::Submitted) {
                throw ProductivityException::timesheetNotReviewable();
            }

            $timesheet->loadMissing('employee');

            if ($timesheet->employee->user_id === $reviewer->id) {
                throw ProductivityException::cannotReviewOwnTimesheet();
            }

            $timesheet->update([
                'status' => $target,
                'approved_by_user_id' => $reviewer->id,
                'approved_at' => now(),
                'review_note' => $note,
            ]);

            return $timesheet->refresh();
        });

        $this->notifier->timesheetReviewed($timesheet, $reviewer, $target);

        return $timesheet;
    }
}
