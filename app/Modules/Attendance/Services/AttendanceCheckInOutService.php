<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Data\LocationVerificationResult;
use App\Modules\Attendance\Enums\AttendanceAction;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Exceptions\AttendanceWorkflowException;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Core\Models\Employee;

class AttendanceCheckInOutService
{
    public function __construct(
        protected AttendanceLocationVerificationService $verification,
        protected AttendanceTimeCalculator $time,
        protected AttendanceBroadcastService $broadcast,
    ) {}

    public function todayEntry(Employee $employee): ?AttendanceEntry
    {
        return AttendanceEntry::query()
            ->with([
                'officeLocation:id,name,late_check_in_time',
                'breaks' => fn ($query) => $query->orderBy('started_at'),
            ])
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', today())
            ->first();
    }

    public function checkIn(Employee $employee, float $latitude, float $longitude, ?string $clientIp = null): AttendanceEntry
    {
        $verification = $this->assertLocationVerified($employee, AttendanceAction::CheckIn, $latitude, $longitude, $clientIp);

        $existing = $this->todayEntry($employee);

        if ($existing !== null && $existing->check_in_at !== null) {
            throw AttendanceWorkflowException::alreadyCheckedIn();
        }

        $now = now();
        $status = AttendanceStatus::Present;

        if ($verification->officeId !== null) {
            $office = OfficeLocation::query()->findOrFail($verification->officeId);
            $status = $office->resolveCheckInStatus($now);
        }

        if ($existing !== null) {
            $existing->update([
                'office_location_id' => $verification->officeId,
                'status' => $status,
                'check_in_at' => $now,
                'check_in_latitude' => $latitude,
                'check_in_longitude' => $longitude,
                'total_break_seconds' => 0,
            ]);

            $this->broadcast->refresh();

            return $this->reloadEntry($existing);
        }

        $entry = AttendanceEntry::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => today(),
            'office_location_id' => $verification->officeId,
            'status' => $status,
            'check_in_at' => $now,
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'total_break_seconds' => 0,
        ])->load([
            'officeLocation:id,name',
            'breaks',
        ]);

        $this->broadcast->refresh();

        return $entry;
    }

    public function checkOut(Employee $employee, float $latitude, float $longitude, ?string $clientIp = null): AttendanceEntry
    {
        $this->assertLocationVerified($employee, AttendanceAction::CheckOut, $latitude, $longitude, $clientIp);

        $entry = $this->todayEntry($employee);

        if ($entry === null || $entry->check_in_at === null) {
            throw AttendanceWorkflowException::mustCheckInFirst();
        }

        if ($entry->check_out_at !== null || $entry->status === AttendanceStatus::CheckedOut) {
            throw AttendanceWorkflowException::alreadyCheckedOut();
        }

        if ($entry->status === AttendanceStatus::OnBreak || $this->time->activeBreak($entry) !== null) {
            throw AttendanceWorkflowException::mustEndBreakBeforeCheckOut();
        }

        $checkOutAt = now();
        $netWorkingSeconds = $this->time->netWorkingSeconds($entry, $checkOutAt);

        $entry->update([
            'status' => AttendanceStatus::CheckedOut,
            'check_out_at' => $checkOutAt,
            'check_out_latitude' => $latitude,
            'check_out_longitude' => $longitude,
            'net_working_seconds' => $netWorkingSeconds,
            'total_working_seconds' => $netWorkingSeconds,
        ]);

        $this->broadcast->refresh();

        return $this->reloadEntry($entry);
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeToday(AttendanceEntry $entry): array
    {
        $activeBreak = $this->time->activeBreak($entry);
        $isOpenSession = $entry->check_in_at !== null && $entry->check_out_at === null;
        $isWorking = $isOpenSession && $entry->status->isWorking();
        $isOnBreak = $isOpenSession && $entry->status === AttendanceStatus::OnBreak;

        return [
            'status' => $entry->status->value,
            'status_label' => $entry->status->label(),
            'check_in_at' => $entry->check_in_at?->toIso8601String(),
            'check_out_at' => $entry->check_out_at?->toIso8601String(),
            'total_break_seconds' => $entry->total_break_seconds,
            'net_working_seconds' => $entry->check_out_at !== null
                ? $entry->net_working_seconds
                : ($isOpenSession ? $this->time->netWorkingSeconds($entry) : null),
            'active_break_started_at' => $activeBreak?->started_at?->toIso8601String(),
            'break_count' => $entry->breaks->count(),
            'office' => $entry->officeLocation ? [
                'id' => $entry->officeLocation->id,
                'name' => $entry->officeLocation->name,
            ] : null,
            'can_check_in' => $entry->check_in_at === null,
            'can_check_out' => $isWorking,
            'can_start_break' => $isWorking,
            'can_resume_work' => $isOnBreak,
        ];
    }

    protected function assertLocationVerified(
        Employee $employee,
        AttendanceAction $action,
        float $latitude,
        float $longitude,
        ?string $clientIp = null,
    ): LocationVerificationResult {
        $verification = $this->verification->verify($employee, $action, $latitude, $longitude, $clientIp);

        if (! $verification->passed) {
            throw AttendanceWorkflowException::locationVerificationFailed($verification->message);
        }

        return $verification;
    }

    /**
     * @return array<string, mixed>
     */
    public function todaySnapshot(Employee $employee): array
    {
        $entry = $this->todayEntry($employee);

        if ($entry === null) {
            return [
                'status' => 'not_checked_in',
                'status_label' => 'Not checked in',
                'check_in_at' => null,
                'check_out_at' => null,
                'total_break_seconds' => 0,
                'net_working_seconds' => null,
                'active_break_started_at' => null,
                'break_count' => 0,
                'office' => null,
                'can_check_in' => true,
                'can_check_out' => false,
                'can_start_break' => false,
                'can_resume_work' => false,
            ];
        }

        return $this->serializeToday($entry);
    }

    protected function reloadEntry(AttendanceEntry $entry): AttendanceEntry
    {
        return $entry->fresh([
            'officeLocation:id,name,late_check_in_time',
            'breaks' => fn ($query) => $query->orderBy('started_at'),
        ]);
    }
}
