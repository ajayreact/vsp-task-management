<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Support\UploadLimits;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a working file below 600 mb is accepted', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf')],
        ])
        ->assertRedirect();

    expect($task->fresh()->getMedia('attachments'))->toHaveCount(1);
});

test('a working file above 600 mb is rejected', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [uploadedFileReportingSize('large.pdf', UploadLimits::MAX_FILE_BYTES + 1, 'application/pdf')],
        ])
        ->assertSessionHasErrors('files.0');

    expect($task->fresh()->getMedia('attachments'))->toHaveCount(0)
        ->and(session('errors')->get('files.0')[0])->toBe(UploadLimits::MAX_FILE_MESSAGE);
});

test('a proof file below 600 mb is accepted', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [UploadedFile::fake()->create('poster.jpg', 100, 'image/jpeg')],
        ])
        ->assertRedirect();

    expect($task->fresh()->deliverables()->sole()->getMedia('proofs'))->toHaveCount(1);
});

test('a proof file above 600 mb is rejected', function () {
    Storage::fake('public');

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/deliverables", [
            'files' => [uploadedFileReportingSize('reel.mp4', UploadLimits::MAX_FILE_BYTES + 1, 'video/mp4')],
        ])
        ->assertSessionHasErrors('files.0');

    expect($task->fresh()->deliverables()->count())->toBe(0);
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
