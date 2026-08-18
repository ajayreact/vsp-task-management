<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\AvailabilityStatus;
use App\Modules\TaskManagement\Models\EmployeeAvailability;
use App\Modules\TaskManagement\Models\EmployeeCapacity;
use App\Modules\TaskManagement\Support\WorkWeek;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CapacityPlanner
{
    public function currentFor(Employee $employee, ?Carbon $on = null): EmployeeCapacity
    {
        $on ??= now();

        $existing = EmployeeCapacity::query()
            ->where('employee_id', $employee->id)
            ->whereDate('effective_from', '<=', $on->toDateString())
            ->orderByDesc('effective_from')
            ->first();

        return $existing ?? new EmployeeCapacity([
            'employee_id' => $employee->id,
            'weekly_hours' => 40,
            'working_days' => EmployeeCapacity::defaultWorkingDays(),
            'effective_from' => $on->copy()->startOfYear(),
        ]);
    }

    /**
     * Hours this person can actually work across the week, after leave.
     */
    public function availableHours(Employee $employee, WorkWeek $week): float
    {
        $capacity = $this->currentFor($employee, $week->start);
        $exceptions = $this->exceptions($employee, $week);
        $total = 0.0;

        foreach ($week->days() as $day) {
            $total += $this->hoursOn($capacity, $exceptions->get($day->toDateString()), $day);
        }

        return round($total, 2);
    }

    public function hoursOn(EmployeeCapacity $capacity, ?EmployeeAvailability $exception, Carbon $day): float
    {
        $baseline = $capacity->worksOn($day) ? $capacity->dailyHours() : 0.0;

        if ($exception === null) {
            return $baseline;
        }

        return match ($exception->status) {
            AvailabilityStatus::Leave, AvailabilityStatus::Holiday => 0.0,
            AvailabilityStatus::HalfDay => $exception->capacity_hours !== null
                ? (float) $exception->capacity_hours
                : round($baseline / 2, 2),
            AvailabilityStatus::Available => $exception->capacity_hours !== null
                ? (float) $exception->capacity_hours
                : $baseline,
        };
    }

    /**
     * @return Collection<string, EmployeeAvailability>
     */
    public function exceptions(Employee $employee, WorkWeek $week): Collection
    {
        return EmployeeAvailability::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$week->start->toDateString(), $week->end->toDateString()])
            ->get()
            ->keyBy(fn (EmployeeAvailability $row) => $row->date->toDateString());
    }
}
