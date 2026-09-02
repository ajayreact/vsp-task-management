<?php

namespace App\Services;

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Enums\WorkMode;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Services\AttendanceTimeCalculator;
use App\Modules\Core\Models\Employee;
use Illuminate\Support\Carbon;

/**
 * Super Admin attendance overview. Lives outside the Attendance module folder
 * so it can read Core employees without circular provider wiring.
 */
class AttendanceDashboard
{
    public function __construct(
        protected AttendanceTimeCalculator $time,
        protected EmployeeOfficeAssignmentService $officeAssignments,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function snapshot(?string $statusFilter = null, ?string $date = null): array
    {
        $statusFilter = $this->normalizeStatusFilter($statusFilter);
        $selectedDate = $this->resolveDate($date);
        $dateString = $selectedDate->toDateString();
        $isToday = $selectedDate->isToday();
        $totalEmployees = Employee::query()->assignable()->count();

        $entries = AttendanceEntry::query()
            ->whereDate('attendance_date', $selectedDate)
            ->get();

        $counts = [
            AttendanceStatus::Present->value => 0,
            AttendanceStatus::Late->value => 0,
            AttendanceStatus::OnBreak->value => 0,
            AttendanceStatus::CheckedOut->value => 0,
        ];

        foreach ($entries as $entry) {
            $value = $entry->status->value;

            if (array_key_exists($value, $counts)) {
                $counts[$value]++;
            }
        }

        $markedCount = AttendanceEntry::query()
            ->whereDate('attendance_date', $selectedDate)
            ->whereNotNull('check_in_at')
            ->count();
        $officePresentCount = AttendanceEntry::query()
            ->whereDate('attendance_date', $selectedDate)
            ->whereNotNull('check_in_at')
            ->where('work_mode', WorkMode::Office)
            ->count();
        $wfhPresentCount = AttendanceEntry::query()
            ->whereDate('attendance_date', $selectedDate)
            ->whereNotNull('check_in_at')
            ->where('work_mode', WorkMode::Wfh)
            ->count();
        $absentCount = max(0, $totalEmployees - $markedCount);
        $base = '/admin/attendance';

        return [
            'date' => $dateString,
            'is_today' => $isToday,
            'filter' => [
                'status' => $statusFilter,
                'date' => $dateString,
            ],
            'overview' => [
                $this->stat(
                    'total_employees',
                    'Total Employees',
                    $totalEmployees,
                    $this->filterHref($base, $dateString),
                    'Active employee profiles',
                ),
                $this->stat(
                    'present_today',
                    $isToday ? 'Present Today' : 'Present',
                    $markedCount,
                    $this->filterHref($base, $dateString, 'present'),
                    "Office: {$officePresentCount} · WFH: {$wfhPresentCount}",
                ),
                $this->stat(
                    'office_present',
                    $isToday ? 'Office Present' : 'Office',
                    $officePresentCount,
                    $this->filterHref($base, $dateString, 'present'),
                ),
                $this->stat(
                    'wfh_present',
                    $isToday ? 'WFH Present' : 'WFH',
                    $wfhPresentCount,
                    $this->filterHref($base, $dateString, 'present'),
                ),
                $this->stat(
                    'absent_today',
                    $isToday ? 'Absent Today' : 'Absent',
                    $absentCount,
                    $this->filterHref($base, $dateString, 'absent'),
                ),
                $this->stat(
                    'late_today',
                    $isToday ? 'Late Today' : 'Late',
                    $counts[AttendanceStatus::Late->value],
                    $this->filterHref($base, $dateString, 'late'),
                ),
                $this->stat(
                    'on_break',
                    'On Break',
                    $counts[AttendanceStatus::OnBreak->value],
                    $this->filterHref($base, $dateString, 'on_break'),
                ),
                $this->stat(
                    'checked_out',
                    'Checked Out',
                    $counts[AttendanceStatus::CheckedOut->value],
                    $this->filterHref($base, $dateString, 'checked_out'),
                ),
            ],
            'records' => $this->recordsForDate($selectedDate, $statusFilter),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function recordsForDate(Carbon $date, ?string $statusFilter = null): array
    {
        if ($statusFilter === 'absent') {
            return $this->absentRecords($date);
        }

        $query = AttendanceEntry::query()
            ->with([
                'employee:id,user_id,employee_code',
                'employee.user:id,name',
                'officeLocation:id,name',
                'breaks' => fn ($query) => $query->orderBy('started_at'),
            ])
            ->whereDate('attendance_date', $date)
            ->whereNotNull('check_in_at')
            ->orderByDesc('check_in_at');

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        return $query
            ->get()
            ->map(fn (AttendanceEntry $entry) => $this->mapEntryRecord($entry, $date->isToday()))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function absentRecords(Carbon $date): array
    {
        $checkedInEmployeeIds = AttendanceEntry::query()
            ->whereDate('attendance_date', $date)
            ->whereNotNull('check_in_at')
            ->pluck('employee_id');

        $employees = Employee::query()
            ->assignable()
            ->with('user:id,name')
            ->when(
                $checkedInEmployeeIds->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $checkedInEmployeeIds),
            )
            ->orderBy('employee_code')
            ->get();

        $offices = $this->officeAssignments->summariesFor($employees->pluck('id')->all());

        return $employees
            ->map(fn (Employee $employee) => [
                'id' => $employee->id * -1,
                'employee' => $employee->user->name,
                'employee_code' => $employee->employee_code,
                'office' => $offices[$employee->id]['name'] ?? '—',
                'status' => 'absent',
                'status_label' => 'Absent',
                'check_in_at' => null,
                'check_out_at' => null,
                'total_break_seconds' => 0,
                'break_count' => 0,
                'net_working_seconds' => null,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapEntryRecord(AttendanceEntry $entry, bool $isToday): array
    {
        return [
            'id' => $entry->id,
            'employee' => $entry->employee->user->name,
            'employee_code' => $entry->employee->employee_code,
            'office' => $entry->officeLocation?->name ?? ($entry->work_mode === WorkMode::Wfh ? 'Work From Home' : '—'),
            'work_mode' => $entry->work_mode->value,
            'work_mode_label' => $entry->work_mode->label(),
            'status' => $entry->status->value,
            'status_label' => $entry->status->label(),
            'check_in_at' => $entry->check_in_at?->toIso8601String(),
            'check_out_at' => $entry->check_out_at?->toIso8601String(),
            'total_break_seconds' => $entry->total_break_seconds + ($isToday ? $this->time->activeBreakSeconds($entry) : 0),
            'break_count' => $entry->breaks->count(),
            'net_working_seconds' => $entry->check_out_at !== null
                ? $entry->net_working_seconds
                : ($isToday ? $this->time->netWorkingSeconds($entry) : null),
        ];
    }

    protected function resolveDate(?string $date): Carbon
    {
        if ($date === null || $date === '') {
            return today();
        }

        try {
            $parsed = Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return today();
        }

        if ($parsed->isFuture()) {
            return today();
        }

        return $parsed;
    }

    protected function filterHref(string $base, string $date, ?string $status = null): string
    {
        $query = ['date' => $date];

        if ($status !== null) {
            $query['status'] = $status;
        }

        return $base.'?'.http_build_query($query);
    }

    protected function normalizeStatusFilter(?string $statusFilter): ?string
    {
        $allowed = ['present', 'absent', 'late', 'on_break', 'checked_out'];

        if ($statusFilter === null || ! in_array($statusFilter, $allowed, true)) {
            return null;
        }

        return $statusFilter;
    }

    /**
     * @return array<string, mixed>
     */
    protected function stat(string $key, string $label, int $count, string $href, ?string $hint = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'count' => $count,
            'display' => (string) $count,
            'href' => $href,
            'hint' => $hint,
        ];
    }
}
