<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\DeliverableStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the assignee submits a proof and the task moves to in review', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'notes' => 'First cut',
            'files' => [UploadedFile::fake()->image('hero.jpg')],
        ])
        ->assertRedirect();

    $deliverable = Deliverable::query()->sole();

    expect($task->refresh()->status)->toBe(TaskStatus::InReview)
        ->and($deliverable->version)->toBe(1)
        ->and($deliverable->status)->toBe(DeliverableStatus::InReview)
        ->and($deliverable->getMedia('proofs'))->toHaveCount(1);
});

test('a reviewer can approve a proof and the task becomes approved', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $reviewer = employeeWith(Ability::AccessTasks, Ability::ReviewDeliverables);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('hero.jpg')],
    ]);

    $deliverable = Deliverable::query()->sole();

    $this->actingAs($reviewer->user)
        ->post("/tasks/deliverables/{$deliverable->id}/review", [
            'decision' => 'approve',
            'comments' => 'Ship it',
        ])
        ->assertRedirect();

    expect($deliverable->refresh()->status)->toBe(DeliverableStatus::Approved)
        ->and($task->refresh()->status)->toBe(TaskStatus::Approved)
        ->and($deliverable->reviews()->sole()->comments)->toBe('Ship it');
});

test('requesting changes sends the task back to revision', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $reviewer = employeeWith(Ability::AccessTasks, Ability::ReviewDeliverables);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('hero.jpg')],
    ]);

    $deliverable = Deliverable::query()->sole();

    $this->actingAs($reviewer->user)->post("/tasks/deliverables/{$deliverable->id}/review", [
        'decision' => 'request_changes',
        'comments' => 'Tighter crop',
    ]);

    expect($task->refresh()->status)->toBe(TaskStatus::Revision)
        ->and($deliverable->refresh()->status)->toBe(DeliverableStatus::ChangesRequested);
});

test('someone without the review ability cannot decide a proof', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InReview,
        'assigned_employee_id' => $employee->id,
    ]);
    $deliverable = Deliverable::factory()->create([
        'tm_task_id' => $task->id,
        'submitted_by_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/deliverables/{$deliverable->id}/review", ['decision' => 'approve'])
        ->assertForbidden();
});

test('a proof cannot be submitted from a draft', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['assigned_employee_id' => $employee->id]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [UploadedFile::fake()->image('hero.jpg')],
        ])
        ->assertForbidden();
});
