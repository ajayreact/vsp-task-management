<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskChecklistItem;

test('an authorized user can create a checklist item', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/checklist-items", ['title' => 'Export final PNG'])
        ->assertRedirect();

    $item = TaskChecklistItem::query()->sole();

    expect($item->tm_task_id)->toBe($task->id)
        ->and($item->title)->toBe('Export final PNG')
        ->and($item->is_completed)->toBeFalse()
        ->and($item->sort_order)->toBe(1);
});

test('an authorized user can update a checklist item title', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $item = TaskChecklistItem::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Draft copy',
        'sort_order' => 1,
    ]);

    $this->actingAs($employee->user)
        ->put("/tasks/{$task->id}/checklist-items/{$item->id}", ['title' => 'Draft headline copy'])
        ->assertRedirect();

    expect($item->fresh()->title)->toBe('Draft headline copy');
});

test('an authorized user can complete and uncomplete a checklist item', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $item = TaskChecklistItem::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Review spacing',
        'sort_order' => 1,
    ]);

    $this->actingAs($employee->user)
        ->patch("/tasks/{$task->id}/checklist-items/{$item->id}/toggle")
        ->assertRedirect();

    $item->refresh();

    expect($item->is_completed)->toBeTrue()
        ->and($item->completed_by_user_id)->toBe($employee->user->id)
        ->and($item->completed_at)->not->toBeNull()
        ->and($task->fresh()->status)->toBe(TaskStatus::InProgress);

    $this->actingAs($employee->user)
        ->patch("/tasks/{$task->id}/checklist-items/{$item->id}/toggle")
        ->assertRedirect();

    $item->refresh();

    expect($item->is_completed)->toBeFalse()
        ->and($item->completed_by_user_id)->toBeNull()
        ->and($item->completed_at)->toBeNull();
});

test('an authorized user can delete a checklist item', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $item = TaskChecklistItem::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Remove me',
        'sort_order' => 1,
    ]);

    $this->actingAs($employee->user)
        ->delete("/tasks/{$task->id}/checklist-items/{$item->id}")
        ->assertRedirect();

    expect(TaskChecklistItem::query()->count())->toBe(0);
});

test('checklist progress counts completed items correctly', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    TaskChecklistItem::query()->create(['tm_task_id' => $task->id, 'title' => 'One', 'sort_order' => 1, 'is_completed' => true, 'completed_by_user_id' => $employee->user->id, 'completed_at' => now()]);
    TaskChecklistItem::query()->create(['tm_task_id' => $task->id, 'title' => 'Two', 'sort_order' => 2, 'is_completed' => true, 'completed_by_user_id' => $employee->user->id, 'completed_at' => now()]);
    TaskChecklistItem::query()->create(['tm_task_id' => $task->id, 'title' => 'Three', 'sort_order' => 3]);

    expect($task->checklistItems()->count())->toBe(3)
        ->and($task->checklistItems()->where('is_completed', true)->count())->toBe(2);
});

test('checklist items cannot be accessed through another tasks route', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $otherTask = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $item = TaskChecklistItem::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Only on task one',
        'sort_order' => 1,
    ]);

    $this->actingAs($employee->user)
        ->delete("/tasks/{$otherTask->id}/checklist-items/{$item->id}")
        ->assertNotFound();
});

test('a bystander cannot manage checklist items on someone elses task', function () {
    $owner = employeeWith(Ability::AccessTasks);
    $bystander = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $owner->id,
    ]);

    $this->actingAs($bystander->user)
        ->post("/tasks/{$task->id}/checklist-items", ['title' => 'Sneak in'])
        ->assertForbidden();
});

test('an authorized user can reorder checklist items', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $first = TaskChecklistItem::query()->create(['tm_task_id' => $task->id, 'title' => 'First', 'sort_order' => 1]);
    $second = TaskChecklistItem::query()->create(['tm_task_id' => $task->id, 'title' => 'Second', 'sort_order' => 2]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/checklist-items/reorder", ['order' => [$second->id, $first->id]])
        ->assertRedirect();

    expect($second->fresh()->sort_order)->toBe(1)
        ->and($first->fresh()->sort_order)->toBe(2);
});
