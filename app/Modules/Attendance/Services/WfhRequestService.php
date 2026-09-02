<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Models\WfhRequest;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class WfhRequestService
{
    public function __construct(protected AttendanceNotifier $notifier) {}

    public function approvedFor(Employee $employee, ?Carbon $date = null): ?WfhRequest
    {
        $date ??= today();

        return WfhRequest::query()
            ->approvedForDate($employee->id, $date)
            ->first();
    }

    public function isApprovedFor(Employee $employee, ?Carbon $date = null): bool
    {
        return $this->approvedFor($employee, $date) !== null;
    }

    public function create(Employee $employee, Carbon $date, string $reason): WfhRequest
    {
        if ($date->isPast() && ! $date->isToday()) {
            throw ValidationException::withMessages([
                'date' => 'You can only request work from home for today or a future date.',
            ]);
        }

        $existing = WfhRequest::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->first();

        if ($existing !== null) {
            throw ValidationException::withMessages([
                'date' => 'You already have a WFH request for this date.',
            ]);
        }

        return WfhRequest::query()->create([
            'employee_id' => $employee->id,
            'date' => $date->toDateString(),
            'reason' => trim($reason),
            'status' => WfhRequestStatus::Pending,
        ]);
    }

    public function approve(WfhRequest $request, User $approver): WfhRequest
    {
        if ($request->status !== WfhRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be approved.',
            ]);
        }

        $request->update([
            'status' => WfhRequestStatus::Approved,
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->notifier->wfhApproved($request->fresh(['employee.user']), $approver);

        return $request->fresh(['employee.user', 'employee.department', 'approver']);
    }

    public function reject(WfhRequest $request, User $approver): WfhRequest
    {
        if ($request->status !== WfhRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending requests can be rejected.',
            ]);
        }

        $request->update([
            'status' => WfhRequestStatus::Rejected,
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->notifier->wfhRejected($request->fresh(['employee.user']), $approver);

        return $request->fresh(['employee.user', 'employee.department', 'approver']);
    }

    /**
     * @return Collection<int, WfhRequest>
     */
    public function forEmployee(Employee $employee): Collection
    {
        return WfhRequest::query()
            ->where('employee_id', $employee->id)
            ->with('approver:id,name')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array{status?: string, employee_id?: int|null, department_id?: int|null, date?: string|null}  $filters
     * @return array{requests: list<array<string, mixed>>, filters: array<string, mixed>}
     */
    public function managementPayload(array $filters = []): array
    {
        $query = WfhRequest::query()
            ->with([
                'employee:id,user_id,employee_code,department_id',
                'employee.user:id,name',
                'employee.department:id,name',
                'approver:id,name',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if (($filters['status'] ?? '') !== '') {
            $query->where('status', $filters['status']);
        }

        if (($filters['employee_id'] ?? null) !== null) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (($filters['department_id'] ?? null) !== null) {
            $query->whereHas('employee', fn (Builder $employee) => $employee->where('department_id', (int) $filters['department_id']));
        }

        if (($filters['date'] ?? '') !== '') {
            $query->whereDate('date', $filters['date']);
        }

        return [
            'filters' => [
                'status' => $filters['status'] ?? '',
                'employee_id' => $filters['employee_id'] ?? null,
                'department_id' => $filters['department_id'] ?? null,
                'date' => $filters['date'] ?? '',
            ],
            'requests' => $query->get()->map(fn (WfhRequest $request) => $this->serialize($request))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(WfhRequest $request): array
    {
        return [
            'id' => $request->id,
            'employee_id' => $request->employee_id,
            'employee' => $request->employee->user->name,
            'employee_code' => $request->employee->employee_code,
            'department' => $request->employee->department?->name ?? '—',
            'date' => $request->date->toDateString(),
            'reason' => $request->reason,
            'status' => $request->status->value,
            'status_label' => $request->status->label(),
            'approved_by' => $request->approver?->name,
            'approved_at' => $request->approved_at?->toIso8601String(),
            'created_at' => $request->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{employees: list<array{id: int, name: string, employee_code: string}>, departments: list<array{id: int, name: string}>}
     */
    public function filterOptions(): array
    {
        $employees = Employee::query()
            ->assignable()
            ->with('user:id,name')
            ->orderBy('employee_code')
            ->get(['id', 'user_id', 'employee_code']);

        $departments = Department::query()
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
        ];
    }
}
