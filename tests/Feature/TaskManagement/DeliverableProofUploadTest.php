<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a working file below 50 mb is accepted', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('brief.pdf', 40 * 1024, 'application/pdf')],
        ])
        ->assertRedirect();

    expect($task->fresh()->getMedia('attachments'))->toHaveCount(1);
});

test('a working file above 50 mb is rejected', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('large.pdf', 51 * 1024, 'application/pdf')],
        ])
        ->assertSessionHasErrors('files.0');

    expect($task->fresh()->getMedia('attachments'))->toHaveCount(0);
});

test('a proof image below 20 mb is accepted', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [UploadedFile::fake()->create('poster.jpg', 15 * 1024, 'image/jpeg')],
        ])
        ->assertRedirect();

    expect($task->fresh()->deliverables()->sole()->getMedia('proofs'))->toHaveCount(1);
});

test('a proof image above 20 mb is rejected', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [UploadedFile::fake()->create('poster.jpg', 21 * 1024, 'image/jpeg')],
        ])
        ->assertSessionHasErrors('files.0');

    expect($task->fresh()->deliverables()->count())->toBe(0);
});

test('a proof video below 100 mb is accepted', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [UploadedFile::fake()->create('reel.mp4', 80 * 1024, 'video/mp4')],
        ])
        ->assertRedirect();

    expect($task->fresh()->deliverables()->sole()->getMedia('proofs'))->toHaveCount(1);
});

test('a proof video above 100 mb is rejected', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [UploadedFile::fake()->create('reel.mp4', 101 * 1024, 'video/mp4')],
        ])
        ->assertSessionHasErrors('files.0');

    expect($task->fresh()->deliverables()->count())->toBe(0);
});

test('a proof document below 50 mb is accepted', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [UploadedFile::fake()->create('layout.psd', 40 * 1024, 'application/octet-stream')],
        ])
        ->assertRedirect();

    expect($task->fresh()->deliverables()->sole()->getMedia('proofs'))->toHaveCount(1);
});

test('unsupported proof file types are rejected', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [UploadedFile::fake()->create('shell.php', 10, 'application/x-php')],
        ])
        ->assertSessionHasErrors('files.0');

    expect($task->fresh()->deliverables()->count())->toBe(0);
});

test('dangerous proof file types are rejected even with allowed extensions in the name', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [UploadedFile::fake()->create('notes.txt', 10, 'text/plain')],
        ])
        ->assertSessionHasErrors('files.0');

    expect($task->fresh()->deliverables()->count())->toBe(0);
});
