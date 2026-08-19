<?php

namespace App\Services;

use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Services\AttendanceTimeCalculator;
use App\Modules\Core\Models\Employee;

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
    public function snapshot(?string $statusFilter = null): array
    {
        $statusFilter = $this->normalizeStatusFilter($statusFilter);
        $today = today();
        $totalEmployees = Employee::query()->assignable()->count();

        $entries = AttendanceEntry::query()
            ->whereDate('attendance_date', $today)
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

        $markedToday = AttendanceEntry::query()
            ->whereDate('attendance_date', $today)
            ->whereNotNull('check_in_at')
            ->count();
        $absentToday = max(0, $totalEmployees - $markedToday);
        $base = '/admin/attendance';

        return [
            'date' => $today->toDateString(),
            'filter' => [
                'status' => $statusFilter,
            ],
            'overview' => [
                $this->stat('total_employees', 'Total Employees', $totalEmployees, $base, 'Active employee profiles'),
                $this->stat('present_today', 'Present Today', $counts[AttendanceStatus::Present->value], $base.'?status=present'),
                $this->stat('absent_today', 'Absent Today', $absentToday, $base.'?status=absent'),
                $this->stat('late_today', 'Late Today', $counts[AttendanceStatus::Late->value], $base.'?status=late'),
                $this->stat('on_break', 'On Break', $counts[AttendanceStatus::OnBreak->value], $base.'?status=on_break'),
                $this->stat('checked_out', 'Checked Out', $counts[AttendanceStatus::CheckedOut->value], $base.'?status=checked_out'),
            ],
            'records' => $this->todayRecords($statusFilter),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function todayRecords(?string $statusFilter = null): array
    {
        if ($statusFilter === 'absent') {
            return $this->absentRecords();
        }

        $query = AttendanceEntry::query()
            ->with([
                'employee:id,user_id,employee_code',
                'employee.user:id,name',
                'officeLocation:id,name',
                'breaks' => fn ($query) => $query->orderBy('started_at'),
            ])
            ->whereDate('attendance_date', today())
            ->whereNotNull('check_in_at')
            ->orderByDesc('check_in_at');

        if ($statusFilter !== null) {
            $query->where('status', $statusFilter);
        }

        return $query
            ->get()
            ->map(fn (AttendanceEntry $entry) => $this->mapEntryRecord($entry))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function absentRecords(): array
    {
        $checkedInEmployeeIds = AttendanceEntry::query()
            ->whereDate('attendance_date', today())
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
    protected function mapEntryRecord(AttendanceEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'employee' => $entry->employee->user->name,
            'employee_code' => $entry->employee->employee_code,
            'office' => $entry->officeLocation?->name ?? '—',
            'status' => $entry->status->value,
            'status_label' => $entry->status->label(),
            'check_in_at' => $entry->check_in_at?->toIso8601String(),
            'check_out_at' => $entry->check_out_at?->toIso8601String(),
            'total_break_seconds' => $entry->total_break_seconds + $this->time->activeBreakSeconds($entry),
            'break_count' => $entry->breaks->count(),
            'net_working_seconds' => $entry->check_out_at !== null
                ? $entry->net_working_seconds
                : $this->time->netWorkingSeconds($entry),
        ];
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
