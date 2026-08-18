<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\AvailabilityStatus;
use App\Modules\TaskManagement\Models\EmployeeAvailability;
use App\Modules\TaskManagement\Models\EmployeeCapacity;
use App\Modules\TaskManagement\Services\CapacityPlanner;
use App\Modules\TaskManagement\Support\WorkWeek;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AvailabilityController extends Controller
{
    public function __construct(protected CapacityPlanner $planner) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', EmployeeAvailability::class);

        $user = $request->user();
        $canManageOthers = $user->can(Ability::ManageCapacity->value);

        $employee = $this->resolveEmployee($request, $canManageOthers);
        $week = WorkWeek::containing($request->date('week') ?? now());

        if ($employee === null) {
            return Inertia::render('TaskManagement/availability/index', [
                'employee' => null,
                'week' => [
                    'start' => $week->start->toDateString(),
                    'end' => $week->end->toDateString(),
                ],
                'capacity' => [
                    'weekly_hours' => 40,
                    'working_days' => EmployeeCapacity::defaultWorkingDays(),
                    'effective_from' => null,
                    'available_hours' => 0,
                ],
                'days' => [],
                'exceptions' => [],
                'employees' => [],
                'statuses' => AvailabilityStatus::options(),
                'can' => [
                    'manage' => false,
                    'capacity' => $canManageOthers,
                ],
            ]);
        }

        $capacity = $this->planner->currentFor($employee, $week->start);

        $exceptionRows = $this->planner->exceptions($employee, $week);

        $exceptions = $exceptionRows
            ->map(fn (EmployeeAvailability $row) => [
                'id' => $row->id,
                'date' => $row->date->toDateString(),
                'status' => $row->status->value,
                'status_label' => $row->status->label(),
                'capacity_hours' => $row->capacity_hours,
                'notes' => $row->notes,
            ])
            ->values();

        $days = collect($week->days())->map(fn ($day) => [
            'date' => $day->toDateString(),
            'weekday' => $day->format('D'),
            'is_working_day' => $capacity->worksOn($day),
            'hours' => $this->planner->hoursOn($capacity, $exceptionRows->get($day->toDateString()), $day),
        ]);

        return Inertia::render('TaskManagement/availability/index', [
            'employee' => [
                'id' => $employee->id,
                'name' => $employee->user->name,
            ],
            'week' => [
                'start' => $week->start->toDateString(),
                'end' => $week->end->toDateString(),
            ],
            'capacity' => [
                'weekly_hours' => $capacity->weekly_hours,
                'working_days' => $capacity->working_days,
                'effective_from' => $capacity->effective_from->toDateString(),
                'available_hours' => $this->planner->availableHours($employee, $week),
            ],
            'days' => $days,
            'exceptions' => $exceptions,
            'employees' => $canManageOthers
                ? Employee::query()->with('user:id,name')->assignable()->orderBy('employee_code')->get(['id', 'user_id', 'employee_code'])
                    ->map(fn (Employee $row) => ['id' => $row->id, 'label' => $row->user->name.' · '.$row->employee_code])
                : [],
            'statuses' => AvailabilityStatus::options(),
            'can' => [
                'manage' => $user->can('manageFor', [EmployeeAvailability::class, $employee]),
                'capacity' => $canManageOthers,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'date' => ['required', 'date'],
            'status' => ['required', Rule::enum(AvailabilityStatus::class)],
            'capacity_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);
        $this->authorize('manageFor', [EmployeeAvailability::class, $employee]);

        EmployeeAvailability::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $validated['date'],
            ],
            [
                'status' => $validated['status'],
                'capacity_hours' => $validated['capacity_hours'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ],
        );

        return back()->with('success', 'Availability updated.');
    }

    public function storeCapacity(Request $request): RedirectResponse
    {
        abort_unless($request->user()->can(Ability::ManageCapacity->value), 403);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'weekly_hours' => ['required', 'numeric', 'min:1', 'max:80'],
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:1,7', 'distinct'],
            'effective_from' => ['required', 'date'],
        ]);

        EmployeeCapacity::query()->updateOrCreate(
            [
                'employee_id' => $validated['employee_id'],
                'effective_from' => $validated['effective_from'],
            ],
            [
                'weekly_hours' => $validated['weekly_hours'],
                'working_days' => array_values(array_map('intval', $validated['working_days'])),
            ],
        );

        return back()->with('success', 'Capacity saved.');
    }

    public function destroy(EmployeeAvailability $availability): RedirectResponse
    {
        $this->authorize('delete', $availability);

        $availability->delete();

        return back()->with('success', 'Exception removed.');
    }

    protected function resolveEmployee(Request $request, bool $canManageOthers): ?Employee
    {
        if ($canManageOthers && $request->integer('employee')) {
            return Employee::query()->with('user:id,name')->findOrFail($request->integer('employee'));
        }

        $own = $request->user()->employee;

        if ($own !== null) {
            return $own->load('user:id,name');
        }

        abort_unless($canManageOthers, 403);

        return Employee::query()->with('user:id,name')->assignable()->orderBy('id')->first();
    }
}
