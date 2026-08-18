<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Enums\TimesheetStatus;
use App\Modules\TaskManagement\Enums\TimeSource;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TimeEntry;
use App\Modules\TaskManagement\Models\Timesheet;

function workerOnTask(): array
{
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create();

    return [$employee, $task];
}

test('starting the timer on an accepted task moves it to in progress', function () {
    [$employee, $task] = workerOnTask();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/timer/start")
        ->assertRedirect();

    expect($task->refresh()->status)->toBe(TaskStatus::InProgress)
        ->and($task->started_at)->not->toBeNull();

    $entry = TimeEntry::query()->sole();

    expect($entry->is_running)->toBeTrue()
        ->and($entry->source)->toBe(TimeSource::Timer)
        ->and($entry->employee_id)->toBe($employee->id);
});

test('a person can only run one timer at a time', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $first = Task::factory()->acceptedBy($employee)->create();
    $second = Task::factory()->acceptedBy($employee)->create();

    $this->actingAs($employee->user)->post("/tasks/{$first->id}/timer/start");

    $this->actingAs($employee->user)
        ->post("/tasks/{$second->id}/timer/start")
        ->assertSessionHas('error');

    expect(TimeEntry::query()->where('is_running', true)->count())->toBe(1);
});

test('pause writes the duration and clears the running flag', function () {
    [$employee, $task] = workerOnTask();

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/timer/start");

    $this->travel(10)->minutes();

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/timer/pause");

    $entry = TimeEntry::query()->sole();

    expect($entry->is_running)->toBeFalse()
        ->and($entry->ended_at)->not->toBeNull()
        ->and($entry->duration_seconds)->toBeGreaterThan(0);
});

test('stop ends the running interval the same way pause does', function () {
    [$employee, $task] = workerOnTask();

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/timer/start");
    $this->actingAs($employee->user)->post("/tasks/{$task->id}/timer/stop");

    expect(TimeEntry::query()->sole()->is_running)->toBeFalse();
});

test('manual entries land on the weekly timesheet', function () {
    [$employee, $task] = workerOnTask();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/time-entries", [
            'started_at' => now()->subHours(2)->toDateTimeString(),
            'ended_at' => now()->subHour()->toDateTimeString(),
            'note' => 'Cutdowns',
        ])
        ->assertRedirect();

    $entry = TimeEntry::query()->sole();
    $sheet = Timesheet::query()->sole();

    expect($entry->source)->toBe(TimeSource::Manual)
        ->and($entry->note)->toBe('Cutdowns')
        ->and($entry->tm_timesheet_id)->toBe($sheet->id)
        ->and((float) $sheet->total_hours)->toBe(1.0)
        ->and($sheet->status)->toBe(TimesheetStatus::Draft);
});

test('time cannot be logged on a draft', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/timer/start")
        ->assertForbidden();
});

test('a bystander cannot start someone elses timer', function () {
    $assignee = employeeWith(Ability::AccessTasks);
    $other = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($assignee)->create();

    $this->actingAs($other->user)
        ->post("/tasks/{$task->id}/timer/start")
        ->assertForbidden();
});
