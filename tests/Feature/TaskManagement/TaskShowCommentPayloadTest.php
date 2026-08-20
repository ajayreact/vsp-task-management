<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskComment;
use Illuminate\Database\QueryException;

test('GET task show loads when discussion comments already exist', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $employee->user->id,
        'body' => 'Saved during a previous failed post attempt',
    ]);

    $this->actingAs($employee->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show-employee')
            ->has('comments', 1)
            ->where('comments.0.body', 'Saved during a previous failed post attempt')
            ->where('comments.0.author_name', $employee->user->name));
});

test('production commentPayload eager load fails on the missing users.avatar column', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $employee->user->id,
        'body' => 'Triggers author eager load',
    ]);

    expect(fn () => $task->comments()->with('author:id,name,avatar')->get())
        ->toThrow(QueryException::class, "Unknown column 'avatar'");
});
