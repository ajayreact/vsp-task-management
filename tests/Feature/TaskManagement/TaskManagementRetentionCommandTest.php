<?php

use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');
});

test('the scheduled cleanup command does nothing when retention is disabled', function () {
    app(TaskManagementRetentionService::class)->writePolicy(false, 7);

    $deliverable = Deliverable::factory()->create();
    $proof = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    ageMedia($proof, 40);

    $this->artisan('files:cleanup')
        ->expectsOutput('Automatic file retention is disabled. No temporary files were deleted.')
        ->assertSuccessful();

    expect($deliverable->fresh()->getMedia('proofs'))->toHaveCount(1)
        ->and(Deliverable::query()->whereKey($deliverable->id)->exists())->toBeTrue();
});

test('the scheduled cleanup command deletes expired temporary files and preserves newer ones', function () {
    app(TaskManagementRetentionService::class)->writePolicy(true, 7);

    $expiredDeliverable = Deliverable::factory()->create();
    $expiredProof = $expiredDeliverable->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('proofs');
    ageMedia($expiredProof, 10);

    $freshDeliverable = Deliverable::factory()->create();
    $freshDeliverable->addMedia(UploadedFile::fake()->image('new.jpg'))->toMediaCollection('proofs');

    $this->artisan('files:cleanup')->assertSuccessful();

    expect($expiredDeliverable->fresh()->getMedia('proofs'))->toHaveCount(0)
        ->and($freshDeliverable->fresh()->getMedia('proofs'))->toHaveCount(1)
        ->and(Deliverable::query()->whereKey($expiredDeliverable->id)->exists())->toBeTrue()
        ->and(Deliverable::query()->whereKey($freshDeliverable->id)->exists())->toBeTrue();
});

test('the scheduled cleanup command deletes expired working files', function () {
    app(TaskManagementRetentionService::class)->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create();
    $proof = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    ageMedia($proof, 12);

    $task = $deliverable->task;
    $attachment = $task->addMedia(UploadedFile::fake()->create('brief.pdf', 20, 'application/pdf'))->toMediaCollection('attachments');
    ageMedia($attachment, 12);

    $this->artisan('files:cleanup')->assertSuccessful();

    expect($deliverable->fresh()->getMedia('proofs'))->toHaveCount(0)
        ->and($task->fresh()->getMedia('attachments'))->toHaveCount(0)
        ->and(Task::query()->whereKey($task->id)->exists())->toBeTrue();
});

test('the legacy proof cleanup command delegates to files cleanup', function () {
    app(TaskManagementRetentionService::class)->writePolicy(false, 7);

    $this->artisan('tasks:cleanup-expired-proofs')
        ->expectsOutput('Automatic file retention is disabled. No temporary files were deleted.')
        ->assertSuccessful();
});

test('the file cleanup command is scheduled to run daily', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('files:cleanup')
        ->assertSuccessful();
});
