<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\AvailabilityStatus;
use App\Modules\TaskManagement\Models\EmployeeAvailability;
use App\Modules\TaskManagement\Models\EmployeeCapacity;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Services\CapacityPlanner;
use App\Modules\TaskManagement\Support\WorkWeek;

test('an employee can log their own leave', function () {
    $employee = employeeWith(Ability::AccessTasks);

    $this->actingAs($employee->user)
        ->post('/tasks/availability', [
            'employee_id' => $employee->id,
            'date' => now()->toDateString(),
            'status' => AvailabilityStatus::Leave->value,
            'notes' => 'Clinic',
        ])
        ->assertRedirect();

    $row = EmployeeAvailability::query()->sole();

    expect($row->employee_id)->toBe($employee->id)
        ->and($row->status)->toBe(AvailabilityStatus::Leave)
        ->and($row->notes)->toBe('Clinic');
});

test('an employee cannot log leave for someone else', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $other = employeeWith(Ability::AccessTasks);

    $this->actingAs($employee->user)
        ->post('/tasks/availability', [
            'employee_id' => $other->id,
            'date' => now()->toDateString(),
            'status' => AvailabilityStatus::Leave->value,
        ])
        ->assertForbidden();
});

test('a manager sets weekly capacity', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageCapacity);
    $employee = employeeWith(Ability::AccessTasks);

    $this->actingAs($manager->user)
        ->post('/tasks/availability/capacity', [
            'employee_id' => $employee->id,
            'weekly_hours' => 32,
            'working_days' => [1, 2, 3, 4],
            'effective_from' => now()->toDateString(),
        ])
        ->assertRedirect();

    $capacity = EmployeeCapacity::query()->sole();

    expect((float) $capacity->weekly_hours)->toBe(32.0)
        ->and($capacity->working_days)->toBe([1, 2, 3, 4]);
});

test('leave removes a day from available hours', function () {
    $employee = employeeWith(Ability::AccessTasks);
    EmployeeCapacity::factory()->create([
        'employee_id' => $employee->id,
        'weekly_hours' => 40,
        'working_days' => [1, 2, 3, 4, 5],
        'effective_from' => now()->startOfYear(),
    ]);

    $week = WorkWeek::containing(now());
    $weekday = collect($week->days())->first(fn ($day) => $day->isoWeekday() <= 5);

    EmployeeAvailability::create([
        'employee_id' => $employee->id,
        'date' => $weekday->toDateString(),
        'status' => AvailabilityStatus::Leave,
    ]);

    $available = app(CapacityPlanner::class)->availableHours($employee, $week);

    expect($available)->toBe(32.0);
});

test('the workload board compares assigned hours to capacity', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewWorkload);
    $employee = employeeWith(Ability::AccessTasks);
    EmployeeCapacity::factory()->create(['employee_id' => $employee->id]);
    Task::factory()->acceptedBy($employee)->create(['estimated_hours' => 20]);

    $this->actingAs($manager->user)
        ->get('/tasks/workload')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/workload')
            ->has('rows.data')
            ->where('rows.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['id'] === $employee->id && (float) $row['assigned_hours'] === 20.0,
            )));
});

test('workload is closed to people without the ability', function () {
    $this->actingAs(employeeWith(Ability::AccessTasks)->user)
        ->get('/tasks/workload')
        ->assertForbidden();
});
