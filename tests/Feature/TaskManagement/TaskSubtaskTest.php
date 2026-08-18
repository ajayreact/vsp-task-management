<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskSubtask;
use App\Modules\TaskManagement\Notifications\StaffDatabaseNotification;
use Illuminate\Support\Facades\Notification;

test('an authorized user can create a subtask', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/subtasks", [
            'title' => 'Prepare copy deck',
            'description' => 'Draft headlines and CTA options.',
        ])
        ->assertRedirect();

    $subtask = TaskSubtask::query()->sole();

    expect($subtask->tm_task_id)->toBe($task->id)
        ->and($subtask->title)->toBe('Prepare copy deck')
        ->and($subtask->status)->toBe(SubtaskStatus::Pending)
        ->and($task->fresh()->status)->toBe(TaskStatus::InProgress);
});

test('an unauthorized user cannot manage another tasks subtasks', function () {
    $owner = employeeWith(Ability::AccessTasks);
    $bystander = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $owner->id,
    ]);

    $this->actingAs($bystander->user)
        ->post("/tasks/{$task->id}/subtasks", ['title' => 'Sneak in'])
        ->assertForbidden();
});

test('an authorized user can assign an employee to a subtask', function () {
    Notification::fake();

    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $assignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $manager->id,
    ]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/subtasks", [
            'title' => 'Resize banner',
            'assigned_employee_id' => $assignee->id,
        ])
        ->assertRedirect();

    $subtask = TaskSubtask::query()->sole();

    expect($subtask->assigned_employee_id)->toBe($assignee->id);

    Notification::assertSentTo($assignee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) use ($task) {
        return $notification->payload['event'] === 'task.subtask_assigned'
            && $notification->payload['task_id'] === $task->id;
    });
});

test('an authorized user can update subtask status and details', function () {
    Notification::fake();

    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $assignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $manager->id,
    ]);
    $subtask = TaskSubtask::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Draft layout',
        'status' => SubtaskStatus::Pending,
        'assigned_employee_id' => $assignee->id,
        'sort_order' => 1,
    ]);

    Notification::fake();

    $this->actingAs($manager->user)
        ->put("/tasks/{$task->id}/subtasks/{$subtask->id}", [
            'title' => 'Draft homepage layout',
            'description' => 'Include mobile and desktop',
            'status' => SubtaskStatus::InProgress->value,
            'assigned_employee_id' => $assignee->id,
            'due_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect();

    $subtask->refresh();

    expect($subtask->title)->toBe('Draft homepage layout')
        ->and($subtask->status)->toBe(SubtaskStatus::InProgress);

    Notification::assertSentTo($assignee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'task.subtask_updated';
    });
});

test('an authorized user can complete and uncomplete a subtask', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $subtask = TaskSubtask::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Export assets',
        'status' => SubtaskStatus::Pending,
        'sort_order' => 1,
    ]);

    $this->actingAs($employee->user)
        ->patch("/tasks/{$task->id}/subtasks/{$subtask->id}/toggle")
        ->assertRedirect();

    $subtask->refresh();

    expect($subtask->status)->toBe(SubtaskStatus::Completed)
        ->and($subtask->completed_at)->not->toBeNull()
        ->and($task->fresh()->status)->toBe(TaskStatus::InProgress);

    $this->actingAs($employee->user)
        ->patch("/tasks/{$task->id}/subtasks/{$subtask->id}/toggle")
        ->assertRedirect();

    $subtask->refresh();

    expect($subtask->status)->toBe(SubtaskStatus::Pending)
        ->and($subtask->completed_at)->toBeNull();
});

test('an authorized user can delete a subtask', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $subtask = TaskSubtask::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Remove me',
        'status' => SubtaskStatus::Pending,
        'sort_order' => 1,
    ]);

    $this->actingAs($employee->user)
        ->delete("/tasks/{$task->id}/subtasks/{$subtask->id}")
        ->assertRedirect();

    expect(TaskSubtask::query()->count())->toBe(0);
});

test('subtasks cannot be accessed through another tasks route', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $otherTask = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $subtask = TaskSubtask::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Only on task one',
        'status' => SubtaskStatus::Pending,
        'sort_order' => 1,
    ]);

    $this->actingAs($employee->user)
        ->delete("/tasks/{$otherTask->id}/subtasks/{$subtask->id}")
        ->assertNotFound();
});

test('subtask progress counts completed items correctly', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    TaskSubtask::query()->create(['tm_task_id' => $task->id, 'title' => 'One', 'status' => SubtaskStatus::Completed, 'completed_at' => now(), 'sort_order' => 1]);
    TaskSubtask::query()->create(['tm_task_id' => $task->id, 'title' => 'Two', 'status' => SubtaskStatus::Completed, 'completed_at' => now(), 'sort_order' => 2]);
    TaskSubtask::query()->create(['tm_task_id' => $task->id, 'title' => 'Three', 'status' => SubtaskStatus::Pending, 'sort_order' => 3]);

    expect($task->subtasks()->count())->toBe(3)
        ->and($task->subtasks()->where('status', SubtaskStatus::Completed)->count())->toBe(2);
});

test('completing all subtasks does not complete the parent task', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $first = TaskSubtask::query()->create(['tm_task_id' => $task->id, 'title' => 'One', 'status' => SubtaskStatus::Pending, 'sort_order' => 1]);
    $second = TaskSubtask::query()->create(['tm_task_id' => $task->id, 'title' => 'Two', 'status' => SubtaskStatus::Pending, 'sort_order' => 2]);

    $this->actingAs($employee->user)->patch("/tasks/{$task->id}/subtasks/{$first->id}/toggle")->assertRedirect();
    $this->actingAs($employee->user)->patch("/tasks/{$task->id}/subtasks/{$second->id}/toggle")->assertRedirect();

    expect($task->fresh()->status)->toBe(TaskStatus::InProgress)
        ->and($task->subtasks()->where('status', SubtaskStatus::Completed)->count())->toBe(2);
});

test('assigning a subtask does not notify the assigner when they assign themselves', function () {
    Notification::fake();

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/subtasks", [
            'title' => 'Self assigned',
            'assigned_employee_id' => $employee->id,
        ])
        ->assertRedirect();

    Notification::assertNotSentTo($employee->user, StaffDatabaseNotification::class);
});
