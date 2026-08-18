<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\AssignmentMode;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('the assignee can attach a working file without touching proofs or status', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('brief.pdf', 120, 'application/pdf')],
        ])
        ->assertRedirect();

    expect($task->refresh()->status)->toBe(TaskStatus::InProgress)
        ->and($task->getMedia('attachments'))->toHaveCount(1)
        ->and($task->getMedia('proofs'))->toHaveCount(0)
        ->and($task->deliverables()->count())->toBe(0)
        ->and($task->getFirstMedia('attachments')?->file_name)->toBe('brief.pdf')
        ->and($task->getFirstMedia('attachments')?->getCustomProperty('uploaded_by_user_id'))->toBe($employee->user->id);
});

test('a manager can attach a working file on someone elses task', function () {
    Storage::fake('public');

    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $assignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $assignee->id,
    ]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->image('moodboard.jpg')],
        ])
        ->assertRedirect();

    expect($task->getMedia('attachments'))->toHaveCount(1);
});

test('a bystander cannot attach files to a task they cannot view', function () {
    Storage::fake('public');

    $owner = employeeWith(Ability::AccessTasks);
    $bystander = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $owner->id,
    ]);

    $this->actingAs($bystander->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('notes.txt', 10, 'text/plain')],
        ])
        ->assertForbidden();
});

test('an open-board lurker cannot attach files until they claim the task', function () {
    Storage::fake('public');

    $lurker = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::Open,
        'assignment_mode' => AssignmentMode::Open,
        'assigned_employee_id' => null,
    ]);

    $this->actingAs($lurker->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('notes.txt', 10, 'text/plain')],
        ])
        ->assertForbidden();
});

test('disallowed file types are rejected', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('shell.php', 20, 'application/x-php')],
        ])
        ->assertSessionHasErrors('files.0');

    expect($task->getMedia('attachments'))->toHaveCount(0);
});

test('the uploader can delete their working file and another employee cannot', function () {
    Storage::fake('public');

    $uploader = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $other = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $other->id,
        'created_by_user_id' => $uploader->user_id,
    ]);

    $this->actingAs($uploader->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('refs.zip', 80, 'application/zip')],
        ])
        ->assertRedirect();

    $media = $task->getFirstMedia('attachments');
    expect($media)->not->toBeNull();

    $this->actingAs($other->user)
        ->delete("/tasks/{$task->id}/attachments/{$media->id}")
        ->assertForbidden();

    $this->actingAs($uploader->user)
        ->delete("/tasks/{$task->id}/attachments/{$media->id}")
        ->assertRedirect();

    expect($task->fresh()->getMedia('attachments'))->toHaveCount(0);
});

test('a manager can delete someone elses working file', function () {
    Storage::fake('public');

    $assignee = employeeWith(Ability::AccessTasks);
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $assignee->id,
    ]);

    $this->actingAs($assignee->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('handover.docx', 40, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')],
        ])
        ->assertRedirect();

    $media = $task->getFirstMedia('attachments');

    $this->actingAs($manager->user)
        ->delete("/tasks/{$task->id}/attachments/{$media->id}")
        ->assertRedirect();

    expect($task->fresh()->getMedia('attachments'))->toHaveCount(0);
});

test('a proof cannot be deleted through the working-files route', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/deliverables", [
        'files' => [UploadedFile::fake()->image('hero.jpg')],
    ]);

    $proof = $task->deliverables()->sole()->getFirstMedia('proofs');
    expect($proof)->not->toBeNull();

    $this->actingAs($employee->user)
        ->delete("/tasks/{$task->id}/attachments/{$proof->id}")
        ->assertNotFound();

    expect($task->deliverables()->sole()->getMedia('proofs'))->toHaveCount(1);
});

test('authorized users can still attach working files after the task is completed', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::Completed,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('final.xlsx', 45 * 1024, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')],
        ])
        ->assertRedirect();

    expect($task->refresh()->status)->toBe(TaskStatus::Completed)
        ->and($task->getMedia('attachments'))->toHaveCount(1);
});
