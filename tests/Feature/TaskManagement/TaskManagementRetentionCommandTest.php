<?php

use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

test('the scheduled cleanup command does nothing when retention is disabled', function () {
    app(TaskManagementRetentionService::class)->writePolicy(false, 7);

    $deliverable = Deliverable::factory()->create(['submitted_at' => now()->subDays(40)]);
    $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');

    $this->artisan('tasks:cleanup-expired-proofs')
        ->expectsOutput('Automatic proof retention is disabled. No files were deleted.')
        ->assertSuccessful();

    expect($deliverable->fresh()->getMedia('proofs'))->toHaveCount(1)
        ->and(Deliverable::query()->whereKey($deliverable->id)->exists())->toBeTrue();
});

test('the scheduled cleanup command deletes expired proofs and preserves newer ones', function () {
    app(TaskManagementRetentionService::class)->writePolicy(true, 7);

    $expired = Deliverable::factory()->create(['submitted_at' => now()->subDays(10)]);
    $expired->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('proofs');

    $fresh = Deliverable::factory()->create(['submitted_at' => now()->subDays(2)]);
    $fresh->addMedia(UploadedFile::fake()->image('new.jpg'))->toMediaCollection('proofs');

    $this->artisan('tasks:cleanup-expired-proofs')->assertSuccessful();

    expect($expired->fresh()->getMedia('proofs'))->toHaveCount(0)
        ->and($fresh->fresh()->getMedia('proofs'))->toHaveCount(1)
        ->and(Deliverable::query()->whereKey($expired->id)->exists())->toBeTrue()
        ->and(Deliverable::query()->whereKey($fresh->id)->exists())->toBeTrue();
});

test('the scheduled cleanup command does not delete task attachments', function () {
    app(TaskManagementRetentionService::class)->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create(['submitted_at' => now()->subDays(12)]);
    $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');

    $task = $deliverable->task;
    $task->addMedia(UploadedFile::fake()->create('brief.pdf', 20, 'application/pdf'))->toMediaCollection('attachments');

    $this->artisan('tasks:cleanup-expired-proofs')->assertSuccessful();

    expect($deliverable->fresh()->getMedia('proofs'))->toHaveCount(0)
        ->and($task->fresh()->getMedia('attachments'))->toHaveCount(1)
        ->and(Task::query()->whereKey($task->id)->exists())->toBeTrue();
});

test('the proof cleanup command is scheduled to run daily', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('tasks:cleanup-expired-proofs')
        ->assertSuccessful();
});
