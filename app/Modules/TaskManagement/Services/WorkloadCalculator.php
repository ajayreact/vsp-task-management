<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\WorkloadSnapshot;
use App\Modules\TaskManagement\Support\WorkWeek;

class WorkloadCalculator
{
    public function __construct(protected CapacityPlanner $planner) {}

    /**
     * @return array{
     *     assigned_hours: float,
     *     available_hours: float,
     *     utilisation_pct: float,
     *     band: string
     * }
     */
    public function forEmployee(Employee $employee, WorkWeek $week): array
    {
        $assigned = (float) Task::query()
            ->where('assigned_employee_id', $employee->id)
            ->whereIn('status', array_map(
                fn (TaskStatus $status) => $status->value,
                array_filter(TaskStatus::cases(), fn (TaskStatus $status) => $status->countsTowardWorkload()),
            ))
            ->sum('estimated_hours');

        $available = $this->planner->availableHours($employee, $week);
        $utilisation = $available > 0 ? round(($assigned / $available) * 100, 1) : ($assigned > 0 ? 100.0 : 0.0);

        $snapshot = [
            'assigned_hours' => round($assigned, 2),
            'available_hours' => $available,
            'utilisation_pct' => $utilisation,
            'band' => $this->band($utilisation, $assigned, $available),
        ];

        WorkloadSnapshot::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $week->start->toDateString(),
            ],
            [
                'assigned_hours' => $snapshot['assigned_hours'],
                'available_hours' => $snapshot['available_hours'],
                'utilisation_pct' => $snapshot['utilisation_pct'],
            ],
        );

        return $snapshot;
    }

    protected function band(float $utilisation, float $assigned, float $available): string
    {
        if ($available <= 0 && $assigned <= 0) {
            return 'unavailable';
        }

        if ($utilisation > 110) {
            return 'overallocated';
        }

        if ($utilisation < 50) {
            return 'bench';
        }

        return 'on_track';
    }
}
