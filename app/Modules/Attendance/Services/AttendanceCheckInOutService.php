<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Data\LocationVerificationResult;
use App\Modules\Attendance\Enums\AttendanceAction;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Enums\WorkMode;
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
        protected WfhRequestService $wfhRequests,
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

        return $this->persistCheckIn(
            $employee,
            officeLocationId: $verification->officeId,
            workMode: WorkMode::Office,
            latitude: $latitude,
            longitude: $longitude,
            status: $this->resolveOfficeCheckInStatus($verification->officeId),
        );
    }

    public function checkInWfh(Employee $employee): AttendanceEntry
    {
        if (! $this->wfhRequests->isApprovedFor($employee)) {
            throw AttendanceWorkflowException::wfhNotApproved();
        }

        return $this->persistCheckIn(
            $employee,
            officeLocationId: null,
            workMode: WorkMode::Wfh,
            latitude: null,
            longitude: null,
            status: AttendanceStatus::Present,
        );
    }

    public function checkOut(Employee $employee, float $latitude, float $longitude, ?string $clientIp = null): AttendanceEntry
    {
        $entry = $this->todayEntry($employee);

        if ($entry === null || $entry->check_in_at === null) {
            throw AttendanceWorkflowException::mustCheckInFirst();
        }

        if ($entry->work_mode !== WorkMode::Wfh) {
            $this->assertLocationVerified($employee, AttendanceAction::CheckOut, $latitude, $longitude, $clientIp);
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
            'check_out_latitude' => $entry->work_mode === WorkMode::Wfh ? null : $latitude,
            'check_out_longitude' => $entry->work_mode === WorkMode::Wfh ? null : $longitude,
            'net_working_seconds' => $netWorkingSeconds,
            'total_working_seconds' => $netWorkingSeconds,
        ]);

        $this->broadcast->refresh();

        return $this->reloadEntry($entry);
    }

    protected function persistCheckIn(
        Employee $employee,
        ?int $officeLocationId,
        WorkMode $workMode,
        ?float $latitude,
        ?float $longitude,
        AttendanceStatus $status,
    ): AttendanceEntry {
        $existing = $this->todayEntry($employee);

        if ($existing !== null && $existing->check_in_at !== null) {
            throw AttendanceWorkflowException::alreadyCheckedIn();
        }

        $now = now();
        $payload = [
            'office_location_id' => $officeLocationId,
            'work_mode' => $workMode,
            'status' => $status,
            'check_in_at' => $now,
            'check_in_latitude' => $latitude,
            'check_in_longitude' => $longitude,
            'total_break_seconds' => 0,
        ];

        if ($existing !== null) {
            $existing->update($payload);
            $this->broadcast->refresh();

            return $this->reloadEntry($existing);
        }

        $entry = AttendanceEntry::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => today(),
            ...$payload,
        ])->load([
            'officeLocation:id,name',
            'breaks',
        ]);

        $this->broadcast->refresh();

        return $entry;
    }

    protected function resolveOfficeCheckInStatus(?int $officeId): AttendanceStatus
    {
        if ($officeId === null) {
            return AttendanceStatus::Present;
        }

        $office = OfficeLocation::query()->findOrFail($officeId);

        return $office->resolveCheckInStatus(now());
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
            'work_mode' => $entry->work_mode->value,
            'work_mode_label' => $entry->work_mode->label(),
            'is_wfh' => $entry->work_mode === WorkMode::Wfh,
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
        $approvedWfh = $this->wfhRequests->approvedFor($employee);

        if ($entry === null) {
            return [
                'status' => 'not_checked_in',
                'status_label' => 'Not checked in',
                'work_mode' => null,
                'work_mode_label' => null,
                'is_wfh' => false,
                'check_in_at' => null,
                'check_out_at' => null,
                'total_break_seconds' => 0,
                'net_working_seconds' => null,
                'active_break_started_at' => null,
                'break_count' => 0,
                'office' => null,
                'can_check_in' => true,
                'can_check_in_wfh' => $approvedWfh !== null,
                'can_check_out' => false,
                'can_start_break' => false,
                'can_resume_work' => false,
                'wfh_request' => $approvedWfh ? [
                    'id' => $approvedWfh->id,
                    'date' => $approvedWfh->date->toDateString(),
                    'status' => $approvedWfh->status->value,
                    'status_label' => $approvedWfh->status->label(),
                ] : null,
            ];
        }

        return array_merge(
            $this->serializeToday($entry),
            [
                'can_check_in_wfh' => false,
                'wfh_request' => $approvedWfh ? [
                    'id' => $approvedWfh->id,
                    'date' => $approvedWfh->date->toDateString(),
                    'status' => $approvedWfh->status->value,
                    'status_label' => $approvedWfh->status->label(),
                ] : null,
            ],
        );
    }

    protected function reloadEntry(AttendanceEntry $entry): AttendanceEntry
    {
        return $entry->fresh([
            'officeLocation:id,name,late_check_in_time',
            'breaks' => fn ($query) => $query->orderBy('started_at'),
        ]);
    }
}
