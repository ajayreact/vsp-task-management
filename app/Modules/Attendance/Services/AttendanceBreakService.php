<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Exceptions\AttendanceWorkflowException;
use App\Modules\Attendance\Models\AttendanceBreak;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Core\Models\Employee;

class AttendanceBreakService
{
    public function __construct(
        protected AttendanceCheckInOutService $attendance,
        protected AttendanceTimeCalculator $time,
        protected AttendanceBroadcastService $broadcast,
    ) {}

    public function startBreak(Employee $employee): AttendanceEntry
    {
        $entry = $this->requireOpenSession($employee);

        if ($entry->status === AttendanceStatus::OnBreak || $this->time->activeBreak($entry) !== null) {
            throw AttendanceWorkflowException::alreadyOnBreak();
        }

        AttendanceBreak::query()->create([
            'attendance_entry_id' => $entry->id,
            'started_at' => now(),
        ]);

        $entry->update(['status' => AttendanceStatus::OnBreak]);

        $this->broadcast->refresh();

        return $this->reloadEntry($entry);
    }

    public function resumeWork(Employee $employee): AttendanceEntry
    {
        $entry = $this->requireOpenSession($employee);

        if ($entry->status !== AttendanceStatus::OnBreak) {
            throw AttendanceWorkflowException::mustBeOnBreakToResume();
        }

        $activeBreak = $this->time->activeBreak($entry);

        if ($activeBreak === null) {
            throw AttendanceWorkflowException::mustBeOnBreakToResume();
        }

        $endedAt = now();
        $duration = (int) $activeBreak->started_at->diffInSeconds($endedAt);

        $activeBreak->update([
            'ended_at' => $endedAt,
            'duration_seconds' => $duration,
        ]);

        $entry->update([
            'status' => $entry->workingStatus(),
            'total_break_seconds' => $entry->total_break_seconds + $duration,
        ]);

        $this->broadcast->refresh();

        return $this->reloadEntry($entry);
    }

    protected function requireOpenSession(Employee $employee): AttendanceEntry
    {
        $entry = $this->attendance->todayEntry($employee);

        if ($entry === null || $entry->check_in_at === null) {
            throw AttendanceWorkflowException::mustCheckInFirst();
        }

        if ($entry->check_out_at !== null || $entry->status === AttendanceStatus::CheckedOut) {
            throw AttendanceWorkflowException::alreadyCheckedOut();
        }

        return $entry;
    }

    protected function reloadEntry(AttendanceEntry $entry): AttendanceEntry
    {
        return $entry->fresh([
            'officeLocation:id,name,late_check_in_time',
            'breaks' => fn ($query) => $query->orderBy('started_at'),
        ]);
    }
}
