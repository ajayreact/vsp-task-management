<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');
});

test('retention disabled means no temporary files are eligible', function () {
    retention()->writePolicy(false, 7);

    $deliverable = Deliverable::factory()->create();
    $proof = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    ageMedia($proof, 40);

    expect(retention()->isEligible($deliverable))->toBeFalse()
        ->and(retention()->eligibleTemporaryMedia())->toHaveCount(0);

    retention()->runCleanup();

    expect($deliverable->fresh()->getMedia('proofs'))->toHaveCount(1);
});

test('seven day retention marks proof files older than seven days as eligible', function () {
    retention()->writePolicy(true, 7);

    $old = Deliverable::factory()->create();
    $oldProof = $old->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('proofs');
    ageMedia($oldProof, 8);

    $fresh = Deliverable::factory()->create();
    $fresh->addMedia(UploadedFile::fake()->image('new.jpg'))->toMediaCollection('proofs');

    expect(retention()->isEligible($old))->toBeTrue()
        ->and(retention()->isEligible($fresh))->toBeFalse();
});

test('fifteen day retention uses the configured window', function () {
    retention()->writePolicy(true, 15);

    $old = Deliverable::factory()->create();
    $oldProof = $old->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('proofs');
    ageMedia($oldProof, 16);

    $inside = Deliverable::factory()->create();
    $inside->addMedia(UploadedFile::fake()->image('new.jpg'))->toMediaCollection('proofs');

    expect(retention()->isEligible($old))->toBeTrue()
        ->and(retention()->isEligible($inside))->toBeFalse();
});

test('thirty day retention uses the configured window', function () {
    retention()->writePolicy(true, 30);

    $old = Deliverable::factory()->create();
    $oldProof = $old->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('proofs');
    ageMedia($oldProof, 31);

    $inside = Deliverable::factory()->create();
    $inside->addMedia(UploadedFile::fake()->image('new.jpg'))->toMediaCollection('proofs');

    expect(retention()->isEligible($old))->toBeTrue()
        ->and(retention()->isEligible($inside))->toBeFalse();
});

test('custom retention days are honoured', function () {
    retention()->writePolicy(true, 5);

    $old = Deliverable::factory()->create();
    $oldProof = $old->addMedia(UploadedFile::fake()->image('old.jpg'))->toMediaCollection('proofs');
    ageMedia($oldProof, 6);

    $inside = Deliverable::factory()->create();
    $inside->addMedia(UploadedFile::fake()->image('new.jpg'))->toMediaCollection('proofs');

    expect(retention()->isEligible($old))->toBeTrue()
        ->and(retention()->isEligible($inside))->toBeFalse();
});

test('newer proof files are not eligible', function () {
    retention()->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create();
    $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');

    expect(retention()->isEligible($deliverable))->toBeFalse();

    retention()->runCleanup();

    expect($deliverable->fresh()->getMedia('proofs'))->toHaveCount(1);
});

test('cleanup deletes proof files and leaves the deliverable record', function () {
    retention()->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create();
    $proof = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    ageMedia($proof, 10);

    retention()->cleanup($deliverable);

    $deliverable->refresh();

    expect($deliverable->exists)->toBeTrue()
        ->and(Deliverable::query()->whereKey($deliverable->id)->exists())->toBeTrue()
        ->and($deliverable->getMedia('proofs'))->toHaveCount(0);
});

test('working files are deleted by retention cleanup when expired', function () {
    retention()->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create();
    $proof = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    ageMedia($proof, 12);

    $attachment = $deliverable->task
        ->addMedia(UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
        ->toMediaCollection('attachments');
    ageMedia($attachment, 12);

    retention()->runCleanup();

    expect(Media::query()->find($proof->id))->toBeNull()
        ->and(Media::query()->find($attachment->id))->toBeNull();
});

test('share links are removed only when no proof files remain', function () {
    retention()->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create();
    $first = $deliverable->addMedia(UploadedFile::fake()->image('one.jpg'))->toMediaCollection('proofs');
    $second = $deliverable->addMedia(UploadedFile::fake()->image('two.jpg'))->toMediaCollection('proofs');
    $link = app(\App\Modules\TaskManagement\Services\DeliverableShareLinkService::class)->getOrCreate($deliverable, \App\Modules\Core\Models\User::factory()->create());

    retention()->deleteProof($first);

    expect(\App\Modules\TaskManagement\Models\DeliverableShareLink::query()->whereKey($link->id)->exists())->toBeTrue()
        ->and($deliverable->fresh()->getMedia('proofs'))->toHaveCount(1);

    retention()->deleteProof($second);

    expect($deliverable->fresh()->getMedia('proofs'))->toHaveCount(0)
        ->and(\App\Modules\TaskManagement\Models\DeliverableShareLink::query()->where('tm_deliverable_id', $deliverable->id)->exists())->toBeFalse()
        ->and(Deliverable::query()->whereKey($deliverable->id)->exists())->toBeTrue();
});

test('an unauthorized employee cannot manually delete a proof', function () {
    $submitter = employeeWith(Ability::AccessTasks);
    $deliverable = Deliverable::factory()->create([
        'submitted_by_employee_id' => $submitter->id,
        'tm_task_id' => Task::factory()->create(['assigned_employee_id' => $submitter->id])->id,
    ]);

    expect($submitter->user->can('deleteProof', $deliverable))->toBeFalse();
});

test('a team lead with tasks.view_all is authorised to delete a proof', function () {
    $submitter = employeeWith(Ability::AccessTasks);
    $lead = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $deliverable = Deliverable::factory()->create([
        'submitted_by_employee_id' => $submitter->id,
    ]);

    expect($lead->user->can('deleteProof', $deliverable))->toBeTrue();
});

test('a super admin is authorised to delete a proof', function () {
    $deliverable = Deliverable::factory()->create();

    expect(superAdmin()->can('deleteProof', $deliverable))->toBeTrue();
});

test('settings live in app_settings with temporary files kept forever by default', function () {
    $payload = \App\Modules\Core\Models\AppSetting::payload('task_management', 'proof_retention');

    expect($payload['enabled'] ?? false)->toBeFalse()
        ->and($payload['days'] ?? null)->toBeNull()
        ->and(retention()->policy())->toMatchArray([
            'enabled' => false,
            'days' => null,
        ]);
});
