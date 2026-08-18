<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\EmployeeStatus;
use App\Modules\TaskManagement\Enums\AssignmentAction;
use App\Modules\TaskManagement\Enums\AssignmentMode;
use App\Modules\TaskManagement\Enums\AssignmentStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;

/*
|--------------------------------------------------------------------------
| Assignment, claiming and acceptance
|--------------------------------------------------------------------------
|
| The heart of the module: how a task gets from "someone should do this" to
| "this person is doing it", and what happens when they say no.
|
*/

function manager()
{
    return employeeWith(
        Ability::AccessTasks,
        Ability::ManageTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
}

function worker()
{
    return employeeWith(Ability::AccessTasks);
}

test('a manager assigns a task and it waits for the employee to accept', function () {
    $manager = manager();
    $employee = worker();
    $task = Task::factory()->create();

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id])
        ->assertRedirect();

    $task->refresh();

    expect($task->status)->toBe(TaskStatus::Assigned)
        ->and($task->assigned_employee_id)->toBe($employee->id)
        ->and($task->assignment_mode)->toBe(AssignmentMode::Direct);

    $assignment = $task->assignments()->sole();

    expect($assignment->mode)->toBe(AssignmentAction::Direct)
        ->and($assignment->status)->toBe(AssignmentStatus::Pending)
        ->and($assignment->assigned_by_user_id)->toBe($manager->user->id);
});

test('assignment is recorded in the status history', function () {
    $manager = manager();
    $employee = worker();
    $task = Task::factory()->create();

    $this->actingAs($manager->user)->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id]);

    $change = $task->statusHistory()->sole();

    expect($change->from_status)->toBe(TaskStatus::Draft)
        ->and($change->to_status)->toBe(TaskStatus::Assigned)
        ->and($change->changed_by_user_id)->toBe($manager->user->id);
});

test('an employee without the assign ability cannot hand out work', function () {
    $employee = worker();
    $task = Task::factory()->create();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id])
        ->assertForbidden();
});

test('a task that has been started cannot be reassigned', function () {
    $manager = manager();
    $employee = worker();
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id])
        ->assertForbidden();
});

test('an employee who has left cannot be assigned work', function () {
    $manager = manager();
    $employee = worker();
    $employee->update(['status' => EmployeeStatus::Exited]);

    $task = Task::factory()->create();

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id])
        ->assertSessionHas('error');

    expect($task->refresh()->assigned_employee_id)->toBeNull();
});

test('reassigning withdraws the offer made to the previous person', function () {
    $manager = manager();
    $first = worker();
    $second = worker();
    $task = Task::factory()->create();

    $this->actingAs($manager->user)->post("/tasks/{$task->id}/assign", ['employee_id' => $first->id]);
    $this->actingAs($manager->user)->post("/tasks/{$task->id}/assign", ['employee_id' => $second->id]);

    $offers = $task->assignments()->orderBy('id')->get();

    expect($offers)->toHaveCount(2)
        ->and($offers[0]->status)->toBe(AssignmentStatus::Reassigned)
        ->and($offers[1]->status)->toBe(AssignmentStatus::Pending)
        ->and($task->refresh()->assigned_employee_id)->toBe($second->id);
});

test('the assignee accepts and the task becomes theirs', function () {
    $employee = worker();
    $task = Task::factory()->awaitingAcceptance($employee)->create();
    $task->assignments()->create([
        'employee_id' => $employee->id,
        'mode' => AssignmentAction::Direct,
        'status' => AssignmentStatus::Pending,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/accept")
        ->assertRedirect();

    expect($task->refresh()->status)->toBe(TaskStatus::InProgress)
        ->and($task->started_at)->not->toBeNull()
        ->and($task->assignments()->sole()->status)->toBe(AssignmentStatus::Accepted);
});

test('nobody else can answer an offer on your behalf', function () {
    $manager = manager();
    $employee = worker();
    $task = Task::factory()->awaitingAcceptance($employee)->create();

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/accept")
        ->assertForbidden();
});

test('declining sends the task back to the open board', function () {
    $employee = worker();
    $task = Task::factory()->awaitingAcceptance($employee)->create();
    $task->assignments()->create([
        'employee_id' => $employee->id,
        'mode' => AssignmentAction::Direct,
        'status' => AssignmentStatus::Pending,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/decline", ['reason' => 'At capacity this week'])
        ->assertRedirect();

    $task->refresh();

    expect($task->status)->toBe(TaskStatus::Open)
        ->and($task->assigned_employee_id)->toBeNull()
        ->and($task->assignment_mode)->toBe(AssignmentMode::Open);

    $offer = $task->assignments()->sole();

    expect($offer->status)->toBe(AssignmentStatus::Declined)
        ->and($offer->decline_reason)->toBe('At capacity this week');
});

test('an employee claims an open task and holds it immediately', function () {
    $employee = worker();
    $task = Task::factory()->open()->create();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/claim")
        ->assertRedirect(route('tasks.show', $task));

    $task->refresh();

    // Accept moves straight into active work — no separate accepted status.
    expect($task->status)->toBe(TaskStatus::InProgress)
        ->and($task->assigned_employee_id)->toBe($employee->id)
        ->and($task->assignment_mode)->toBe(AssignmentMode::Direct)
        ->and($task->started_at)->not->toBeNull();

    $claim = $task->assignments()->sole();

    expect($claim->mode)->toBe(AssignmentAction::Claim)
        ->and($claim->status)->toBe(AssignmentStatus::Accepted)
        ->and($claim->assigned_by_user_id)->toBeNull()
        ->and($claim->responded_at)->not->toBeNull();
});

test('a task already claimed cannot be claimed again', function () {
    $first = worker();
    $second = worker();
    $task = Task::factory()->open()->create();

    $this->actingAs($first->user)->post("/tasks/{$task->id}/claim");

    $this->actingAs($second->user)
        ->post("/tasks/{$task->id}/claim")
        ->assertRedirect()
        ->assertSessionHas('error', 'This task has already been claimed.');

    expect($task->refresh()->assigned_employee_id)->toBe($first->id);
});

test('a directly assigned task cannot be claimed off the board', function () {
    $employee = worker();
    $other = worker();
    $task = Task::factory()->awaitingAcceptance($other)->create();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/claim")
        ->assertForbidden();
});

test('publishing puts a draft on the open board and clears the assignee', function () {
    $manager = manager();
    $employee = worker();
    $task = Task::factory()->awaitingAcceptance($employee)->create();
    $task->assignments()->create([
        'employee_id' => $employee->id,
        'mode' => AssignmentAction::Direct,
        'status' => AssignmentStatus::Pending,
    ]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/publish")
        ->assertRedirect();

    $task->refresh();

    expect($task->status)->toBe(TaskStatus::Open)
        ->and($task->assigned_employee_id)->toBeNull()
        ->and($task->assignments()->sole()->status)->toBe(AssignmentStatus::Reassigned);
});

test('the open board lists only unclaimed open tasks', function () {
    $employee = worker();
    Task::factory()->open()->create(['title' => 'Up for grabs']);
    Task::factory()->create(['title' => 'Still a draft']);
    Task::factory()->acceptedBy($employee)->create(['title' => 'Already taken']);

    $this->actingAs($employee->user)
        ->get('/tasks/board')
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/board')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.title', 'Up for grabs'));
});
