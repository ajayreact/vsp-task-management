<?php

namespace App\Services;

use App\Modules\Attendance\Models\EmployeeOfficeAssignment;
use App\Modules\Attendance\Models\OfficeLocation;
use App\Modules\Core\Models\Employee;
use Illuminate\Support\Collection;

/**
 * Bridges Core employees and Attendance office locations without putting
 * Attendance foreign keys on the shared kernel.
 */
class EmployeeOfficeAssignmentService
{
    /**
     * @return Collection<int, OfficeLocation>
     */
    public function selectableOffices(?int $currentOfficeId = null): Collection
    {
        return OfficeLocation::query()
            ->where(function ($query) use ($currentOfficeId) {
                $query->where('is_active', true);

                if ($currentOfficeId !== null) {
                    $query->orWhere('id', $currentOfficeId);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function assign(Employee $employee, ?int $officeLocationId): void
    {
        if ($officeLocationId === null) {
            EmployeeOfficeAssignment::query()
                ->where('employee_id', $employee->id)
                ->delete();

            return;
        }

        EmployeeOfficeAssignment::query()->updateOrCreate(
            ['employee_id' => $employee->id],
            ['office_location_id' => $officeLocationId],
        );
    }

    public function officeIdFor(int $employeeId): ?int
    {
        return EmployeeOfficeAssignment::query()
            ->where('employee_id', $employeeId)
            ->value('office_location_id');
    }

    public function assignedOfficeFor(Employee $employee): ?OfficeLocation
    {
        return EmployeeOfficeAssignment::query()
            ->where('employee_id', $employee->id)
            ->with('officeLocation')
            ->first()
            ?->officeLocation;
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array<int, array{id: int, name: string}>
     */
    public function summariesFor(array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        return EmployeeOfficeAssignment::query()
            ->whereIn('employee_id', $employeeIds)
            ->with('officeLocation:id,name')
            ->get()
            ->mapWithKeys(function (EmployeeOfficeAssignment $assignment) {
                $office = $assignment->officeLocation;

                if ($office === null) {
                    return [];
                }

                return [
                    $assignment->employee_id => [
                        'id' => $office->id,
                        'name' => $office->name,
                    ],
                ];
            })
            ->all();
    }
}
