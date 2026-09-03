<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Enums\WfhRequestType;
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

    public function authorizedFor(Employee $employee, ?Carbon $date = null): ?WfhRequest
    {
        $date ??= today();

        $assignment = WfhRequest::query()
            ->assignmentForDate($employee->id, $date)
            ->first();

        if ($assignment !== null) {
            return $assignment;
        }

        return WfhRequest::query()
            ->approvedRequestForDate($employee->id, $date)
            ->first();
    }

    public function isAuthorizedFor(Employee $employee, ?Carbon $date = null): bool
    {
        if ($employee->work_arrangement->bypassesOfficeGps()) {
            return true;
        }

        return $this->authorizedFor($employee, $date) !== null;
    }

    /** @deprecated Use authorizedFor() */
    public function approvedFor(Employee $employee, ?Carbon $date = null): ?WfhRequest
    {
        return $this->authorizedFor($employee, $date);
    }

    /** @deprecated Use isAuthorizedFor() */
    public function isApprovedFor(Employee $employee, ?Carbon $date = null): bool
    {
        return $this->isAuthorizedFor($employee, $date);
    }

    public function createRequest(Employee $employee, User $requester, Carbon $startDate, Carbon $endDate, string $reason): WfhRequest
    {
        [$startDate, $endDate] = $this->normalizeRange($startDate, $endDate);

        if ($startDate->isPast() && ! $startDate->isToday()) {
            throw ValidationException::withMessages([
                'start_date' => 'You can only request work from home for today or a future date.',
            ]);
        }

        $this->assertNoConflicts($employee->id, $startDate, $endDate);

        return WfhRequest::query()->create([
            'employee_id' => $employee->id,
            'type' => WfhRequestType::Request,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'reason' => trim($reason),
            'status' => WfhRequestStatus::Pending,
            'requested_by_user_id' => $requester->id,
        ]);
    }

    public function assignDirect(
        Employee $employee,
        User $assigner,
        Carbon $startDate,
        Carbon $endDate,
        string $reason,
        ?string $notes = null,
    ): WfhRequest {
        [$startDate, $endDate] = $this->normalizeRange($startDate, $endDate);

        $this->assertNoBlockingConflicts($employee->id, $startDate, $endDate);

        $assignment = WfhRequest::query()->create([
            'employee_id' => $employee->id,
            'type' => WfhRequestType::Assignment,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'reason' => trim($reason),
            'notes' => $notes !== null ? trim($notes) : null,
            'status' => WfhRequestStatus::Assigned,
            'assigned_by_user_id' => $assigner->id,
            'approved_by_user_id' => $assigner->id,
            'approved_at' => now(),
        ]);

        $this->resolvePendingConflicts($employee->id, $startDate, $endDate, $assigner);

        $this->notifier->wfhAssigned($assignment->fresh(['employee.user']), $assigner);

        return $assignment->fresh(['employee.user', 'employee.department', 'assigner']);
    }

    public function updateAssignment(
        WfhRequest $assignment,
        User $editor,
        Carbon $startDate,
        Carbon $endDate,
        string $reason,
        ?string $notes = null,
    ): WfhRequest {
        if ($assignment->type !== WfhRequestType::Assignment || $assignment->status !== WfhRequestStatus::Assigned) {
            throw ValidationException::withMessages([
                'status' => 'Only active direct assignments can be edited.',
            ]);
        }

        [$startDate, $endDate] = $this->normalizeRange($startDate, $endDate);

        $this->assertNoBlockingConflicts($assignment->employee_id, $startDate, $endDate, $assignment->id);

        $assignment->update([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'reason' => trim($reason),
            'notes' => $notes !== null ? trim($notes) : null,
            'assigned_by_user_id' => $editor->id,
            'approved_by_user_id' => $editor->id,
            'approved_at' => now(),
        ]);

        return $assignment->fresh(['employee.user', 'employee.department', 'assigner', 'approver']);
    }

    public function cancelAssignment(WfhRequest $assignment, User $actor): WfhRequest
    {
        if ($assignment->type !== WfhRequestType::Assignment || $assignment->status !== WfhRequestStatus::Assigned) {
            throw ValidationException::withMessages([
                'status' => 'Only active direct assignments can be cancelled.',
            ]);
        }

        $assignment->update([
            'status' => WfhRequestStatus::Cancelled,
            'approved_by_user_id' => $actor->id,
            'approved_at' => now(),
        ]);

        return $assignment->fresh(['employee.user', 'employee.department', 'assigner', 'approver']);
    }

    public function approve(WfhRequest $request, User $approver): WfhRequest
    {
        if ($request->type !== WfhRequestType::Request || $request->status !== WfhRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending employee requests can be approved.',
            ]);
        }

        $this->assertNoConflicts(
            $request->employee_id,
            $request->start_date,
            $request->end_date,
            $request->id,
        );

        $request->update([
            'status' => WfhRequestStatus::Approved,
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->notifier->wfhApproved($request->fresh(['employee.user']), $approver);

        return $request->fresh(['employee.user', 'employee.department', 'approver', 'requester']);
    }

    public function reject(WfhRequest $request, User $approver): WfhRequest
    {
        if ($request->type !== WfhRequestType::Request || $request->status !== WfhRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'status' => 'Only pending employee requests can be rejected.',
            ]);
        }

        $request->update([
            'status' => WfhRequestStatus::Rejected,
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
        ]);

        $this->notifier->wfhRejected($request->fresh(['employee.user']), $approver);

        return $request->fresh(['employee.user', 'employee.department', 'approver', 'requester']);
    }

    /**
     * @return Collection<int, WfhRequest>
     */
    public function forEmployee(Employee $employee): Collection
    {
        return WfhRequest::query()
            ->where('employee_id', $employee->id)
            ->with(['approver:id,name', 'requester:id,name', 'assigner:id,name'])
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array{status?: string, type?: string, employee_id?: int|null, department_id?: int|null, date?: string|null}  $filters
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
                'requester:id,name',
                'assigner:id,name',
            ])
            ->orderByDesc('start_date')
            ->orderByDesc('id');

        if (($filters['status'] ?? '') !== '') {
            $query->where('status', $filters['status']);
        }

        if (($filters['type'] ?? '') !== '') {
            $query->where('type', $filters['type']);
        }

        if (($filters['employee_id'] ?? null) !== null) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        if (($filters['department_id'] ?? null) !== null) {
            $query->whereHas('employee', fn (Builder $employee) => $employee->where('department_id', (int) $filters['department_id']));
        }

        if (($filters['date'] ?? '') !== '') {
            $date = Carbon::parse($filters['date']);
            $query->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date);
        }

        return [
            'filters' => [
                'status' => $filters['status'] ?? '',
                'type' => $filters['type'] ?? '',
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
        $sourceUser = $request->type === WfhRequestType::Assignment
            ? $request->assigner
            : $request->requester;

        return [
            'id' => $request->id,
            'employee_id' => $request->employee_id,
            'employee' => $request->employee->user->name,
            'employee_code' => $request->employee->employee_code,
            'department' => $request->employee->department?->name ?? '—',
            'type' => $request->type->value,
            'type_label' => $request->type->label(),
            'source_label' => $request->type === WfhRequestType::Assignment
                ? ($request->assigner?->name ? 'Assigned by '.$request->assigner->name : 'Assigned by Operations')
                : 'Requested by Employee',
            'start_date' => $request->start_date->toDateString(),
            'end_date' => $request->end_date->toDateString(),
            'date' => $request->start_date->toDateString(),
            'date_range_label' => $request->dateRangeLabel(),
            'reason' => $request->reason,
            'notes' => $request->notes,
            'status' => $request->status->value,
            'status_label' => $request->status->label(),
            'approved_by' => $request->approver?->name,
            'approved_at' => $request->approved_at?->toIso8601String(),
            'requested_by' => $request->requester?->name,
            'assigned_by' => $request->assigner?->name,
            'created_at' => $request->created_at?->toIso8601String(),
            'can_approve' => $request->type === WfhRequestType::Request && $request->status === WfhRequestStatus::Pending,
            'can_reject' => $request->type === WfhRequestType::Request && $request->status === WfhRequestStatus::Pending,
            'can_edit' => $request->type === WfhRequestType::Assignment && $request->status === WfhRequestStatus::Assigned,
            'can_cancel' => $request->type === WfhRequestType::Assignment && $request->status === WfhRequestStatus::Assigned,
        ];
    }

    /**
     * @return array{employees: list<array{id: int, name: string, employee_code: string, department_id: int|null, department: string}>, departments: list<array{id: int, name: string}>}
     */
    public function filterOptions(): array
    {
        $employees = Employee::query()
            ->assignable()
            ->with(['user:id,name', 'department:id,name'])
            ->orderBy('employee_code')
            ->get(['id', 'user_id', 'employee_code', 'department_id']);

        $departments = Department::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'employees' => $employees->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->user->name,
                'employee_code' => $employee->employee_code,
                'department_id' => $employee->department_id,
                'department' => $employee->department?->name ?? '—',
            ])->all(),
            'departments' => $departments->map(fn (Department $department) => [
                'id' => $department->id,
                'name' => $department->name,
            ])->all(),
        ];
    }

    /**
     * @return list<WfhRequest>
     */
    public function findConflicts(int $employeeId, Carbon $startDate, Carbon $endDate, ?int $excludeId = null): array
    {
        return WfhRequest::query()
            ->overlappingRange($employeeId, $startDate, $endDate, $excludeId)
            ->get()
            ->all();
    }

    protected function assertNoConflicts(int $employeeId, Carbon $startDate, Carbon $endDate, ?int $excludeId = null): void
    {
        $conflicts = $this->findConflicts($employeeId, $startDate, $endDate, $excludeId);

        if ($conflicts === []) {
            return;
        }

        $conflict = $conflicts[0];
        $message = $conflict->type === WfhRequestType::Request && $conflict->status === WfhRequestStatus::Pending
            ? 'An existing WFH request exists for this employee and date range.'
            : 'This employee already has WFH authorization that overlaps the selected date range.';

        throw ValidationException::withMessages([
            'start_date' => $message,
        ]);
    }

    protected function assertNoBlockingConflicts(int $employeeId, Carbon $startDate, Carbon $endDate, ?int $excludeId = null): void
    {
        $conflicts = array_filter(
            $this->findConflicts($employeeId, $startDate, $endDate, $excludeId),
            fn (WfhRequest $conflict) => ! ($conflict->type === WfhRequestType::Request && $conflict->status === WfhRequestStatus::Pending),
        );

        if ($conflicts === []) {
            return;
        }

        throw ValidationException::withMessages([
            'start_date' => 'This employee already has WFH authorization that overlaps the selected date range.',
        ]);
    }

    public function hasPendingConflicts(int $employeeId, Carbon $startDate, Carbon $endDate): bool
    {
        foreach ($this->findConflicts($employeeId, $startDate, $endDate) as $conflict) {
            if ($conflict->type === WfhRequestType::Request && $conflict->status === WfhRequestStatus::Pending) {
                return true;
            }
        }

        return false;
    }

    protected function resolvePendingConflicts(int $employeeId, Carbon $startDate, Carbon $endDate, User $actor): void
    {
        WfhRequest::query()
            ->where('employee_id', $employeeId)
            ->where('type', WfhRequestType::Request)
            ->where('status', WfhRequestStatus::Pending)
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->each(function (WfhRequest $request) use ($actor): void {
                $request->update([
                    'status' => WfhRequestStatus::Rejected,
                    'approved_by_user_id' => $actor->id,
                    'approved_at' => now(),
                ]);

                $this->notifier->wfhRejected($request->fresh(['employee.user']), $actor);
            });
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function normalizeRange(Carbon $startDate, Carbon $endDate): array
    {
        $startDate = $startDate->copy()->startOfDay();
        $endDate = $endDate->copy()->startOfDay();

        if ($endDate->lt($startDate)) {
            throw ValidationException::withMessages([
                'end_date' => 'The end date must be on or after the start date.',
            ]);
        }

        return [$startDate, $endDate];
    }
}
