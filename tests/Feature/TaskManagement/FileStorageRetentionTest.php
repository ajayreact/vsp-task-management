<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\CompanyDocument;
use App\Modules\TaskManagement\Models\ContentCalendarItem;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Services\MediaStorageService;
use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use App\Modules\TaskManagement\Support\UploadLimits;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');
});

test('working files are deleted after configured retention period', function () {
    retention()->writePolicy(true, 7);

    $task = Task::factory()->create();
    $attachment = $task->addMedia(UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
        ->toMediaCollection('attachments');
    ageMedia($attachment, 8);

    retention()->runCleanup();

    expect(Media::query()->find($attachment->id))->toBeNull()
        ->and($task->fresh()->getMedia('attachments'))->toHaveCount(0);
});

test('creative review proof files are deleted after configured retention period', function () {
    retention()->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create();
    $proof = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    ageMedia($proof, 8);

    retention()->runCleanup();

    expect(Media::query()->find($proof->id))->toBeNull()
        ->and($deliverable->fresh()->getMedia('proofs'))->toHaveCount(0);
});

test('seven day retention only deletes files older than seven days', function () {
    retention()->writePolicy(true, 7);

    $task = Task::factory()->create();
    $old = $task->addMedia(UploadedFile::fake()->create('old.txt', 10, 'text/plain'))->toMediaCollection('attachments');
    $fresh = $task->addMedia(UploadedFile::fake()->create('new.txt', 10, 'text/plain'))->toMediaCollection('attachments');
    ageMedia($old, 8);

    retention()->runCleanup();

    expect(Media::query()->find($old->id))->toBeNull()
        ->and(Media::query()->find($fresh->id))->not->toBeNull();
});

test('fifteen day retention only deletes files older than fifteen days', function () {
    retention()->writePolicy(true, 15);

    $deliverable = Deliverable::factory()->create();
    $old = $deliverable->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('proofs');
    $inside = $deliverable->addMedia(UploadedFile::fake()->image('new.jpg'))->toMediaCollection('proofs');
    ageMedia($old, 16);

    retention()->runCleanup();

    expect(Media::query()->find($old->id))->toBeNull()
        ->and(Media::query()->find($inside->id))->not->toBeNull();
});

test('thirty day retention only deletes files older than thirty days', function () {
    retention()->writePolicy(true, 30);

    $task = Task::factory()->create();
    $old = $task->addMedia(UploadedFile::fake()->create('old.pdf', 10, 'application/pdf'))->toMediaCollection('attachments');
    $inside = $task->addMedia(UploadedFile::fake()->create('new.pdf', 10, 'application/pdf'))->toMediaCollection('attachments');
    ageMedia($old, 31);

    retention()->runCleanup();

    expect(Media::query()->find($old->id))->toBeNull()
        ->and(Media::query()->find($inside->id))->not->toBeNull();
});

test('company logo library files are never deleted by automatic cleanup', function () {
    retention()->writePolicy(true, 7);

    $company = Company::factory()->create();
    $logo = $company->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logos');
    ageMedia($logo, 30);

    retention()->runCleanup();

    expect(Media::query()->find($logo->id))->not->toBeNull();
});

test('operations documents are never deleted by automatic cleanup', function () {
    retention()->writePolicy(true, 7);

    $document = CompanyDocument::factory()->create();
    $file = $document->addMedia(UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf'))->toMediaCollection('file');
    ageMedia($file, 30);

    retention()->runCleanup();

    expect(Media::query()->find($file->id))->not->toBeNull()
        ->and(CompanyDocument::query()->whereKey($document->id)->exists())->toBeTrue();
});

test('content calendar attachments are never deleted by automatic cleanup', function () {
    retention()->writePolicy(true, 7);

    $item = ContentCalendarItem::factory()->create();
    $attachment = $item->addMedia(UploadedFile::fake()->image('post.png'))->toMediaCollection('attachments');
    ageMedia($attachment, 30);

    retention()->runCleanup();

    expect(Media::query()->find($attachment->id))->not->toBeNull()
        ->and(ContentCalendarItem::query()->whereKey($item->id)->exists())->toBeTrue();
});

test('manual deletion of a permanent document removes database and physical file', function () {
    $document = CompanyDocument::factory()->create();
    $media = $document->addMedia(UploadedFile::fake()->create('brief.pdf', 10, 'application/pdf'))->toMediaCollection('file');
    $path = $media->getPathRelativeToRoot();
    $mediaStorage = app(MediaStorageService::class);

    expect(Storage::disk('public')->exists($path))->toBeTrue();

    $mediaStorage->deleteMedia($media, 'manual_document_delete', allowPermanent: true);
    $document->delete();

    expect(CompanyDocument::query()->whereKey($document->id)->exists())->toBeFalse()
        ->and(Media::query()->find($media->id))->toBeNull()
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});

test('automatic cleanup removes physical storage for temporary files', function () {
    retention()->writePolicy(true, 7);

    $task = Task::factory()->create();
    $attachment = $task->addMedia(UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
        ->toMediaCollection('attachments');
    $path = $attachment->getPathRelativeToRoot();
    ageMedia($attachment, 8);

    expect(Storage::disk('public')->exists($path))->toBeTrue();

    retention()->runCleanup();

    expect(Storage::disk('public')->exists($path))->toBeFalse()
        ->and(Media::query()->find($attachment->id))->toBeNull();
});

test('missing physical files do not crash automatic cleanup', function () {
    retention()->writePolicy(true, 7);

    $task = Task::factory()->create();
    $attachment = $task->addMedia(UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
        ->toMediaCollection('attachments');
    ageMedia($attachment, 8);

    Storage::disk('public')->delete($attachment->getPathRelativeToRoot());

    retention()->runCleanup();

    expect(Media::query()->find($attachment->id))->toBeNull();
});

test('upload validation rejects files over 600 mb', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['assigned_employee_id' => $employee->id]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [uploadedFileReportingSize('large.pdf', UploadLimits::MAX_FILE_BYTES + 1, 'application/pdf')],
        ])
        ->assertSessionHasErrors('files.0');

    expect($task->fresh()->getMedia('attachments'))->toHaveCount(0);
});

test('upload validation accepts files below 600 mb', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['assigned_employee_id' => $employee->id]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/attachments", [
            'files' => [UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf')],
        ])
        ->assertRedirect();

    expect($task->fresh()->getMedia('attachments'))->toHaveCount(1);
});

test('media storage service refuses automatic deletion of permanent files', function () {
    $company = Company::factory()->create();
    $logo = $company->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logos');

    expect(fn () => app(MediaStorageService::class)->deleteMedia($logo, 'automatic_retention'))
        ->toThrow(\InvalidArgumentException::class);
});

test('files cleanup command is scheduled daily', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('files:cleanup')
        ->assertSuccessful();
});
