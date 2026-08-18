<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\AppSetting;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\DeliverableReview;
use App\Modules\TaskManagement\Models\DeliverableShareLink;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Services\DeliverableShareLinkService;
use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

beforeEach(function () {
    Storage::fake('public');
});

function retention(): TaskManagementRetentionService
{
    return app(TaskManagementRetentionService::class);
}

test('retention disabled means no proof files are eligible', function () {
    retention()->writePolicy(false, 7);

    $old = Deliverable::factory()->create(['submitted_at' => now()->subDays(40)]);
    $old->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');

    expect(retention()->isEligible($old))->toBeFalse()
        ->and(retention()->eligibleDeliverables())->toHaveCount(0);

    retention()->cleanup($old);

    expect($old->fresh()->getMedia('proofs'))->toHaveCount(1);
});

test('seven day retention marks proofs older than seven days as eligible', function () {
    retention()->writePolicy(true, 7);

    $old = Deliverable::factory()->create(['submitted_at' => now()->subDays(8)]);
    $fresh = Deliverable::factory()->create(['submitted_at' => now()->subDays(3)]);

    expect(retention()->isEligible($old))->toBeTrue()
        ->and(retention()->isEligible($fresh))->toBeFalse();
});

test('fifteen day retention uses the configured window', function () {
    retention()->writePolicy(true, 15);

    $old = Deliverable::factory()->create(['submitted_at' => now()->subDays(16)]);
    $inside = Deliverable::factory()->create(['submitted_at' => now()->subDays(10)]);

    expect(retention()->isEligible($old))->toBeTrue()
        ->and(retention()->isEligible($inside))->toBeFalse();
});

test('thirty day retention uses the configured window', function () {
    retention()->writePolicy(true, 30);

    $old = Deliverable::factory()->create(['submitted_at' => now()->subDays(31)]);
    $inside = Deliverable::factory()->create(['submitted_at' => now()->subDays(20)]);

    expect(retention()->isEligible($old))->toBeTrue()
        ->and(retention()->isEligible($inside))->toBeFalse();
});

test('custom retention days are honoured', function () {
    retention()->writePolicy(true, 5);

    $old = Deliverable::factory()->create(['submitted_at' => now()->subDays(6)]);
    $inside = Deliverable::factory()->create(['submitted_at' => now()->subDays(4)]);

    expect(retention()->isEligible($old))->toBeTrue()
        ->and(retention()->isEligible($inside))->toBeFalse();
});

test('newer proof files are not eligible', function () {
    retention()->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create(['submitted_at' => now()->subHours(12)]);
    $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');

    expect(retention()->isEligible($deliverable))->toBeFalse();

    retention()->cleanup($deliverable);

    expect($deliverable->fresh()->getMedia('proofs'))->toHaveCount(1);
});

test('cleanup deletes only deliverable proofs and leaves the deliverable record', function () {
    retention()->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create(['submitted_at' => now()->subDays(10)]);
    $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');
    $deliverable->reviews()->create([
        'reviewer_user_id' => User::factory()->create()->id,
        'round' => 1,
        'decision' => 'approve',
        'comments' => 'Ship it',
        'reviewed_at' => now()->subDays(9),
    ]);

    $task = $deliverable->task;
    $task->addMedia(UploadedFile::fake()->create('brief.pdf', 20, 'application/pdf'))->toMediaCollection('attachments');

    retention()->cleanup($deliverable);

    $deliverable->refresh();

    expect($deliverable->exists)->toBeTrue()
        ->and(Deliverable::query()->whereKey($deliverable->id)->exists())->toBeTrue()
        ->and($deliverable->getMedia('proofs'))->toHaveCount(0)
        ->and(DeliverableReview::query()->where('tm_deliverable_id', $deliverable->id)->count())->toBe(1)
        ->and($task->fresh()->getMedia('attachments'))->toHaveCount(1)
        ->and(Task::query()->whereKey($task->id)->exists())->toBeTrue();
});

test('task attachments are not deleted by retention cleanup', function () {
    retention()->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create(['submitted_at' => now()->subDays(12)]);
    $proof = $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');

    $attachment = $deliverable->task
        ->addMedia(UploadedFile::fake()->create('notes.txt', 10, 'text/plain'))
        ->toMediaCollection('attachments');

    retention()->cleanup($deliverable);

    expect(Media::query()->find($proof->id))->toBeNull()
        ->and(Media::query()->find($attachment->id))->not->toBeNull()
        ->and($attachment->collection_name)->toBe('attachments');
});

test('share links are removed only when no proof files remain', function () {
    retention()->writePolicy(true, 7);

    $deliverable = Deliverable::factory()->create(['submitted_at' => now()->subDays(10)]);
    $first = $deliverable->addMedia(UploadedFile::fake()->image('one.jpg'))->toMediaCollection('proofs');
    $second = $deliverable->addMedia(UploadedFile::fake()->image('two.jpg'))->toMediaCollection('proofs');
    $link = app(DeliverableShareLinkService::class)->getOrCreate($deliverable, User::factory()->create());

    retention()->deleteProof($first);

    expect(DeliverableShareLink::query()->whereKey($link->id)->exists())->toBeTrue()
        ->and($deliverable->fresh()->getMedia('proofs'))->toHaveCount(1);

    retention()->deleteProof($second);

    expect($deliverable->fresh()->getMedia('proofs'))->toHaveCount(0)
        ->and(DeliverableShareLink::query()->where('tm_deliverable_id', $deliverable->id)->exists())->toBeFalse()
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

test('settings live in app_settings with proofs kept forever by default', function () {
    $payload = AppSetting::payload('task_management', 'proof_retention');

    expect($payload['enabled'] ?? false)->toBeFalse()
        ->and($payload['days'] ?? null)->toBeNull()
        ->and(retention()->policy())->toMatchArray([
            'enabled' => false,
            'days' => null,
        ]);
});
