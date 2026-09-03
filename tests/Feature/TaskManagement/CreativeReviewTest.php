<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\DeliverableStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Http\Controllers\DeliverableController;
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

test('a reviewer can approve a proof and keep the task under review for the client', function () {
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

    $deliverable->refresh();
    $task->refresh();

    expect($deliverable->status)->toBe(DeliverableStatus::Approved)
        ->and($task->status)->toBe(TaskStatus::InReview)
        ->and($deliverable->shareLink)->not->toBeNull()
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

test('the assignee can upload a corrected version while the task is in review', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('v1.jpg')],
    ]);

    expect($task->refresh()->status)->toBe(TaskStatus::InReview);

    $first = Deliverable::query()->where('tm_task_id', $task->id)->where('version', 1)->sole();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'notes' => 'Fixed typo',
            'files' => [UploadedFile::fake()->image('v2.jpg')],
        ])
        ->assertRedirect();

    $second = Deliverable::query()->where('tm_task_id', $task->id)->where('version', 2)->sole();

    expect(Deliverable::query()->where('tm_task_id', $task->id)->count())->toBe(2)
        ->and($first->refresh()->status)->toBe(DeliverableStatus::Superseded)
        ->and($second->status)->toBe(DeliverableStatus::InReview)
        ->and($task->refresh()->status)->toBe(TaskStatus::InReview)
        ->and($second->shareLink)->not->toBeNull();
});

test('a reviewer can only decide the latest open deliverable version', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $reviewer = employeeWith(Ability::AccessTasks, Ability::ReviewDeliverables);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('v1.jpg')],
    ]);

    $first = Deliverable::query()->where('version', 1)->sole();

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('v2.jpg')],
    ]);

    $this->actingAs($reviewer->user)
        ->post("/tasks/deliverables/{$first->id}/review", ['decision' => 'approve'])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('submitting a proof creates a share link the assignee can copy', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
        'title' => 'Need To Create a Course Flyer',
        'description' => 'Using the 3 courses, create an attractive flyer.',
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('flyer.jpg')],
    ]);

    $deliverable = Deliverable::query()->sole();

    expect($deliverable->shareLink)->not->toBeNull();

    $this->actingAs($employee->user)
        ->post(route('tasks.deliverables.share-link', $deliverable))
        ->assertRedirect()
        ->assertSessionHas('share_url')
        ->assertSessionHas('share_message', DeliverableController::buildShareMessage($task, $deliverable->shareLink->publicUrl()));
});

test('the share message includes only the task title and review link', function () {
    $task = Task::factory()->create([
        'title' => 'Create a Krishna Janmashtami Poster',
        'description' => 'Using brand colors, create an attractive poster.',
    ]);

    $message = DeliverableController::buildShareMessage($task, 'https://app.vspcrm.in/d/8KJujQ6M');

    expect($message)->toBe("Create a Krishna Janmashtami Poster\nhttps://app.vspcrm.in/d/8KJujQ6M");
});
