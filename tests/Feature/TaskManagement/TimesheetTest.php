<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\Timesheet;

test('the owner submits a draft timesheet', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create();

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/time-entries", [
        'started_at' => now()->subHours(3)->toDateTimeString(),
        'ended_at' => now()->subHours(1)->toDateTimeString(),
    ]);

    $sheet = Timesheet::query()->sole();

    $this->actingAs($employee->user)
        ->post("/tasks/timesheets/{$sheet->id}/submit")
        ->assertRedirect();

    expect($sheet->refresh()->status)->toBe(TimesheetStatus::Submitted)
        ->and($sheet->submitted_at)->not->toBeNull();
});

test('submitted weeks reject new time entries', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create();

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/time-entries", [
        'started_at' => now()->subHours(2)->toDateTimeString(),
        'ended_at' => now()->subHour()->toDateTimeString(),
    ]);

    $sheet = Timesheet::query()->sole();
    $this->actingAs($employee->user)->post("/tasks/timesheets/{$sheet->id}/submit");

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/time-entries", [
            'started_at' => now()->subMinutes(40)->toDateTimeString(),
            'ended_at' => now()->subMinutes(10)->toDateTimeString(),
        ])
        ->assertSessionHas('error');
});

test('a manager approves someone elses timesheet', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $manager = employeeWith(Ability::AccessTasks, Ability::ApproveTimesheets);
    $sheet = Timesheet::factory()->submitted()->create(['employee_id' => $employee->id]);

    $this->actingAs($manager->user)
        ->post("/tasks/timesheets/{$sheet->id}/approve", ['note' => 'Looks right'])
        ->assertRedirect();

    expect($sheet->refresh()->status)->toBe(TimesheetStatus::Approved)
        ->and($sheet->approved_by_user_id)->toBe($manager->user->id)
        ->and($sheet->review_note)->toBe('Looks right');
});

test('you cannot approve your own timesheet', function () {
    $employee = employeeWith(Ability::AccessTasks, Ability::ApproveTimesheets);
    $sheet = Timesheet::factory()->submitted()->create(['employee_id' => $employee->id]);

    $this->actingAs($employee->user)
        ->post("/tasks/timesheets/{$sheet->id}/approve")
        ->assertForbidden();
});

test('rejection returns the sheet to a state that can be edited again', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $manager = employeeWith(Ability::AccessTasks, Ability::ApproveTimesheets);
    $task = Task::factory()->acceptedBy($employee)->create();
    $sheet = Timesheet::factory()->submitted()->create(['employee_id' => $employee->id]);

    $this->actingAs($manager->user)->post("/tasks/timesheets/{$sheet->id}/reject", ['note' => 'Missing Friday']);

    expect($sheet->refresh()->status)->toBe(TimesheetStatus::Rejected);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/time-entries", [
            'started_at' => now()->subHours(2)->toDateTimeString(),
            'ended_at' => now()->subHour()->toDateTimeString(),
        ])
        ->assertRedirect();
});

test('an employee without view-all only sees their own timesheets', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $other = employeeWith(Ability::AccessTasks);
    Timesheet::factory()->create(['employee_id' => $employee->id]);
    Timesheet::factory()->create(['employee_id' => $other->id]);

    $this->actingAs($employee->user)
        ->get('/tasks/timesheets')
        ->assertInertia(fn ($page) => $page->has('timesheets.data', 1));
});
