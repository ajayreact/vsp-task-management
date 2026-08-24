<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Enums\AttendanceReportCode;
use App\Modules\Attendance\Enums\AttendanceStatus;
use App\Modules\Attendance\Models\AttendanceEntry;
use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Services\EmployeeOfficeAssignmentService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AttendanceReportService
{
    public function __construct(
        protected AttendanceTimeCalculator $time,
        protected EmployeeOfficeAssignmentService $officeAssignments,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function filterOptions(): array
    {
        $employees = Employee::query()
            ->assignable()
            ->with('user:id,name')
            ->orderBy('employee_code')
            ->get();

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $offices = OfficeLocation::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'employees' => $employees->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->user->name,
                'employee_code' => $employee->employee_code,
            ])->all(),
            'departments' => $departments->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
            ])->all(),
            'offices' => $offices->map(fn (OfficeLocation $office) => [
                'id' => $office->id,
                'name' => $office->name,
            ])->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function dailyTable(
        ?string $date = null,
        ?string $statusFilter = null,
        ?int $employeeId = null,
        ?string $search = null,
    ): array {
        $selectedDate = $this->resolveDate($date);
        $statusFilter = $this->normalizeStatusFilter($statusFilter);
        $employees = $this->filteredEmployees($employeeId, null, null, $search);
        $officeSummaries = $this->officeAssignments->summariesFor($employees->pluck('id')->all());

        $entries = AttendanceEntry::query()
            ->with([
                'employee:id,user_id,employee_code',
                'employee.user:id,name',
                'officeLocation:id,name,late_check_in_time',
                'breaks' => fn ($query) => $query->orderBy('started_at'),
            ])
            ->whereDate('attendance_date', $selectedDate)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->keyBy('employee_id');

        $isToday = $selectedDate->isToday();
        $records = [];

        if ($statusFilter === 'absent') {
            foreach ($employees as $employee) {
                if ($entries->has($employee->id)) {
                    continue;
                }

                if ($this->isWeekOff($selectedDate)) {
                    continue;
                }

                $records[] = $this->mapAbsentRecord($employee, $selectedDate, $officeSummaries);
            }
        } elseif ($statusFilter !== null) {
            foreach ($entries as $entry) {
                if (! $this->entryMatchesStatusFilter($entry, $statusFilter)) {
                    continue;
                }

                if (! $employees->contains('id', $entry->employee_id)) {
                    continue;
                }

                $records[] = $this->mapEntryRecord($entry, $selectedDate, $isToday);
            }
        } else {
            foreach ($employees as $employee) {
                $entry = $entries->get($employee->id);

                if ($entry !== null && $entry->check_in_at !== null) {
                    $records[] = $this->mapEntryRecord($entry, $selectedDate, $isToday);
                } elseif ($this->isWeekOff($selectedDate)) {
                    $records[] = $this->mapWeekOffRecord($employee, $selectedDate, $officeSummaries);
                } else {
                    $records[] = $this->mapAbsentRecord($employee, $selectedDate, $officeSummaries);
                }
            }
        }

        usort($records, fn (array $left, array $right) => strcmp($left['employee_code'], $right['employee_code']));

        return [
            'date' => $selectedDate->toDateString(),
            'day' => $selectedDate->format('l'),
            'is_today' => $isToday,
            'filter' => [
                'status' => $statusFilter,
                'date' => $selectedDate->toDateString(),
                'employee_id' => $employeeId,
                'search' => $search,
            ],
            'records' => $records,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function monthlyReport(
        int $month,
        int $year,
        ?int $employeeId = null,
        ?int $departmentId = null,
        ?int $officeId = null,
    ): array {
        [$month, $year] = $this->resolveMonthYear($month, $year);
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth()->startOfDay();
        $today = today()->startOfDay();

        $employees = $this->filteredEmployees($employeeId, $departmentId, $officeId, null);
        $employeeIds = $employees->pluck('id')->all();
        $officeSummaries = $this->officeAssignments->summariesFor($employeeIds);

        $entries = AttendanceEntry::query()
            ->with(['officeLocation:id,late_check_in_time'])
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->keyBy(fn (AttendanceEntry $entry) => $entry->employee_id.'|'.$entry->attendance_date->toDateString());

        $days = [];
        $cursor = $periodStart->copy();

        while ($cursor->lte($periodEnd)) {
            $days[] = [
                'date' => $cursor->toDateString(),
                'day' => (int) $cursor->format('j'),
                'weekday' => $cursor->format('D'),
                'is_weekend' => $this->isWeekOff($cursor),
                'is_future' => $cursor->greaterThan($today),
            ];
            $cursor->addDay();
        }

        $rows = [];
        $summary = [
            'total_employees' => $employees->count(),
            'working_days' => 0,
            'present' => 0,
            'absent' => 0,
            'late' => 0,
            'week_off' => 0,
            'average_working_hours' => 0.0,
        ];

        $totalNetSeconds = 0;
        $presentDayCount = 0;

        foreach ($days as $day) {
            if (! $day['is_weekend'] && ! $day['is_future']) {
                $summary['working_days']++;
            }
        }

        foreach ($employees as $employee) {
            $dailyCodes = [];
            $rowCounts = [
                'present' => 0,
                'absent' => 0,
                'late' => 0,
                'week_off' => 0,
                'net_seconds' => 0,
            ];

            foreach ($days as $day) {
                $entry = $entries->get($employee->id.'|'.$day['date']);

                $code = $this->resolveDailyCode(
                    Carbon::parse($day['date']),
                    $entry,
                    $day['is_future'],
                );

                $dailyCodes[] = [
                    'date' => $day['date'],
                    'code' => $code->value,
                    'label' => $code->label(),
                    'is_weekend' => $day['is_weekend'],
                    'is_future' => $day['is_future'],
                ];

                if ($code->countsTowardPresent()) {
                    $rowCounts['present']++;
                } elseif ($code->countsTowardAbsent()) {
                    $rowCounts['absent']++;
                } elseif ($code->countsTowardLate()) {
                    $rowCounts['late']++;
                } elseif ($code->countsTowardWeekOff()) {
                    $rowCounts['week_off']++;
                }

                if ($entry !== null && $entry->check_in_at !== null && ! $day['is_future']) {
                    $netSeconds = $entry->check_out_at !== null
                        ? (int) ($entry->net_working_seconds ?? 0)
                        : ($day['date'] === $today->toDateString()
                            ? $this->time->netWorkingSeconds($entry)
                            : 0);

                    $rowCounts['net_seconds'] += $netSeconds;
                    $totalNetSeconds += $netSeconds;

                    if ($code->countsTowardPresent() || $code->countsTowardLate()) {
                        $presentDayCount++;
                    }
                }
            }

            $summary['present'] += $rowCounts['present'];
            $summary['absent'] += $rowCounts['absent'];
            $summary['late'] += $rowCounts['late'];
            $summary['week_off'] += $rowCounts['week_off'];

            $rows[] = [
                'employee_id' => $employee->id,
                'employee' => $employee->user->name,
                'employee_code' => $employee->employee_code,
                'department' => $employee->department?->name ?? '—',
                'office' => $officeSummaries[$employee->id]['name'] ?? '—',
                'days' => $dailyCodes,
                'totals' => [
                    'present' => $rowCounts['present'],
                    'absent' => $rowCounts['absent'],
                    'late' => $rowCounts['late'],
                    'week_off' => $rowCounts['week_off'],
                    'net_seconds' => $rowCounts['net_seconds'],
                    'average_hours' => $rowCounts['present'] + $rowCounts['late'] > 0
                        ? round($rowCounts['net_seconds'] / 3600 / ($rowCounts['present'] + $rowCounts['late']), 2)
                        : 0.0,
                ],
            ];
        }

        $summary['average_working_hours'] = $presentDayCount > 0
            ? round($totalNetSeconds / 3600 / $presentDayCount, 2)
            : 0.0;

        return [
            'month' => $month,
            'year' => $year,
            'label' => $periodStart->format('F Y'),
            'days' => $days,
            'rows' => $rows,
            'summary' => $summary,
            'filter' => [
                'month' => $month,
                'year' => $year,
                'employee_id' => $employeeId,
                'department_id' => $departmentId,
                'office_id' => $officeId,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function employeeMonthlyDetail(int $employeeId, int $month, int $year): array
    {
        [$month, $year] = $this->resolveMonthYear($month, $year);
        $employee = Employee::query()
            ->assignable()
            ->with(['user:id,name', 'department:id,name'])
            ->findOrFail($employeeId);

        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth()->startOfDay();
        $today = today()->startOfDay();

        $entries = AttendanceEntry::query()
            ->with([
                'officeLocation:id,name,late_check_in_time',
                'breaks' => fn ($query) => $query->orderBy('started_at'),
            ])
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->get()
            ->keyBy(fn (AttendanceEntry $entry) => $entry->attendance_date->toDateString());

        $records = [];
        $cursor = $periodStart->copy();

        while ($cursor->lte($periodEnd)) {
            if ($cursor->greaterThan($today)) {
                break;
            }

            $entry = $entries->get($cursor->toDateString());
            $code = $this->resolveDailyCode($cursor, $entry, false);

            if ($entry !== null && $entry->check_in_at !== null) {
                $records[] = array_merge(
                    $this->mapEntryRecord($entry, $cursor, $cursor->isToday()),
                    [
                        'report_code' => $code->value,
                        'report_label' => $code->label(),
                    ],
                );
            } else {
                $records[] = [
                    'id' => $employee->id * -1,
                    'employee' => $employee->user->name,
                    'employee_code' => $employee->employee_code,
                    'date' => $cursor->toDateString(),
                    'day' => $cursor->format('l'),
                    'office' => '—',
                    'status' => $code === AttendanceReportCode::WeekOff ? 'week_off' : 'absent',
                    'status_label' => $code->label() !== '' ? $code->label() : 'Absent',
                    'is_late' => false,
                    'check_in_at' => null,
                    'check_out_at' => null,
                    'total_break_seconds' => 0,
                    'break_count' => 0,
                    'net_working_seconds' => null,
                    'report_code' => $code->value,
                    'report_label' => $code->label(),
                ];
            }

            $cursor->addDay();
        }

        return [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name,
                'employee_code' => $employee->employee_code,
                'department' => $employee->department?->name ?? '—',
            ],
            'month' => $month,
            'year' => $year,
            'label' => $periodStart->format('F Y'),
            'records' => $records,
        ];
    }

    /**
     * @return Collection<int, Employee>
     */
    protected function filteredEmployees(
        ?int $employeeId,
        ?int $departmentId,
        ?int $officeId,
        ?string $search,
    ): Collection {
        $query = Employee::query()
            ->assignable()
            ->with([
                'user:id,name',
                'department:id,name',
            ])
            ->orderBy('employee_code');

        if ($employeeId !== null) {
            $query->whereKey($employeeId);
        }

        if ($departmentId !== null) {
            $query->where('department_id', $departmentId);
        }

        if ($officeId !== null) {
            $assignedEmployeeIds = EmployeeOfficeAssignment::query()
                ->where('office_location_id', $officeId)
                ->pluck('employee_id');

            $query->whereIn('id', $assignedEmployeeIds);
        }

        if ($search !== null && trim($search) !== '') {
            $term = '%'.trim($search).'%';
            $query->where(function (Builder $builder) use ($term) {
                $builder
                    ->where('employee_code', 'like', $term)
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $term));
            });
        }

        return $query->get();
    }

    protected function entryMatchesStatusFilter(AttendanceEntry $entry, string $statusFilter): bool
    {
        if ($statusFilter === 'absent') {
            return false;
        }

        return $entry->status->value === $statusFilter;
    }

    /**
     * @param  array<int, array{id: int, name: string}>  $officeSummaries
     * @return array<string, mixed>
     */
    protected function mapWeekOffRecord(Employee $employee, Carbon $date, array $officeSummaries): array
    {
        return [
            'id' => $employee->id * -1,
            'employee_id' => $employee->id,
            'employee' => $employee->user->name,
            'employee_code' => $employee->employee_code,
            'date' => $date->toDateString(),
            'day' => $date->format('l'),
            'office' => $officeSummaries[$employee->id]['name'] ?? '—',
            'status' => 'week_off',
            'status_label' => 'Week off',
            'is_late' => false,
            'check_in_at' => null,
            'check_out_at' => null,
            'total_break_seconds' => 0,
            'break_count' => 0,
            'net_working_seconds' => null,
        ];
    }

    /**
     * @param  array<int, array{id: int, name: string}>  $officeSummaries
     * @return array<string, mixed>
     */
    protected function mapAbsentRecord(Employee $employee, Carbon $date, array $officeSummaries): array
    {
        return [
            'id' => $employee->id * -1,
            'employee_id' => $employee->id,
            'employee' => $employee->user->name,
            'employee_code' => $employee->employee_code,
            'date' => $date->toDateString(),
            'day' => $date->format('l'),
            'office' => $officeSummaries[$employee->id]['name'] ?? '—',
            'status' => 'absent',
            'status_label' => 'Absent',
            'is_late' => false,
            'check_in_at' => null,
            'check_out_at' => null,
            'total_break_seconds' => 0,
            'break_count' => 0,
            'net_working_seconds' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapEntryRecord(AttendanceEntry $entry, Carbon $date, bool $isToday): array
    {
        $workingStatus = $this->resolveWorkingStatusForReport($entry);

        return [
            'id' => $entry->id,
            'employee_id' => $entry->employee_id,
            'employee' => $entry->employee->user->name,
            'employee_code' => $entry->employee->employee_code,
            'date' => $date->toDateString(),
            'day' => $date->format('l'),
            'office' => $entry->officeLocation?->name ?? '—',
            'status' => $entry->status->value,
            'status_label' => $entry->status->label(),
            'is_late' => $entry->status === AttendanceStatus::Late || $workingStatus === AttendanceStatus::Late,
            'check_in_at' => $entry->check_in_at?->toIso8601String(),
            'check_out_at' => $entry->check_out_at?->toIso8601String(),
            'total_break_seconds' => $entry->total_break_seconds + ($isToday ? $this->time->activeBreakSeconds($entry) : 0),
            'break_count' => $entry->breaks->count(),
            'net_working_seconds' => $entry->check_out_at !== null
                ? $entry->net_working_seconds
                : ($isToday ? $this->time->netWorkingSeconds($entry) : null),
        ];
    }

    protected function resolveDailyCode(Carbon $date, ?AttendanceEntry $entry, bool $isFuture): AttendanceReportCode
    {
        if ($isFuture) {
            return AttendanceReportCode::Future;
        }

        if ($this->isWeekOff($date)) {
            return AttendanceReportCode::WeekOff;
        }

        if ($entry === null || $entry->check_in_at === null) {
            return AttendanceReportCode::Absent;
        }

        if ($entry->status === AttendanceStatus::Late) {
            return AttendanceReportCode::Late;
        }

        return $this->resolveWorkingStatusForReport($entry) === AttendanceStatus::Late
            ? AttendanceReportCode::Late
            : AttendanceReportCode::Present;
    }

    protected function resolveWorkingStatusForReport(AttendanceEntry $entry): AttendanceStatus
    {
        if ($entry->check_in_at === null) {
            return AttendanceStatus::Present;
        }

        $office = $entry->officeLocation;

        if ($office === null || $office->late_check_in_time === null) {
            return AttendanceStatus::Present;
        }

        $deadline = Carbon::parse($entry->attendance_date->toDateString().' '.$office->late_check_in_time);

        return $entry->check_in_at->greaterThan($deadline)
            ? AttendanceStatus::Late
            : AttendanceStatus::Present;
    }

    protected function isWeekOff(Carbon $date): bool
    {
        return $date->isWeekend();
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

    /**
     * @return array{0: int, 1: int}
     */
    protected function resolveMonthYear(int $month, int $year): array
    {
        $month = max(1, min(12, $month));
        $current = today();
        $year = max(2000, min($current->year, $year));

        if ($year === $current->year && $month > $current->month) {
            $month = $current->month;
        }

        return [$month, $year];
    }

    protected function normalizeStatusFilter(?string $statusFilter): ?string
    {
        $allowed = ['present', 'absent', 'late', 'on_break', 'checked_out'];

        if ($statusFilter === null || ! in_array($statusFilter, $allowed, true)) {
            return null;
        }

        return $statusFilter;
    }
}
