<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\AppSetting;
use App\Modules\TaskManagement\Enums\NotificationSystemSound;
use App\Modules\TaskManagement\Models\Deliverable;
use App\Modules\TaskManagement\Models\NotificationSoundAsset;
use App\Modules\TaskManagement\Services\TaskManagementNotificationSoundService;
use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutVite();
    Storage::fake('local');
});

test('a super admin can view notification sound settings on the settings page', function () {
    $this->actingAs(superAdmin())
        ->get('/tasks/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/settings/index')
            ->where('notificationSound.enabled', true)
            ->where('notificationSound.source', 'system')
            ->where('notificationSound.system_sound', NotificationSystemSound::ClassicChime->value)
            ->has('notificationSound.system_sounds', 5));
});

test('a team lead cannot change notification sound settings', function () {
    $lead = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $lead->user->syncRoles(Role::findOrCreate(SystemRole::TeamLead->value, 'web'));

    $this->actingAs($lead->user)
        ->put('/tasks/settings/notification-sound', [
            'enabled' => false,
            'source' => 'system',
            'system_sound' => NotificationSystemSound::SoftBell->value,
        ])
        ->assertForbidden();
});

test('an employee cannot change notification sound settings', function () {
    $this->actingAs(employeeWith(Ability::AccessTasks)->user)
        ->put('/tasks/settings/notification-sound', [
            'enabled' => false,
            'source' => 'system',
            'system_sound' => NotificationSystemSound::SoftBell->value,
        ])
        ->assertForbidden();
});

test('default notification sound settings exist', function () {
    expect(AppSetting::payload('task_management', 'notification_sound'))->toMatchArray([
        'enabled' => true,
        'source' => 'system',
        'system_sound' => NotificationSystemSound::ClassicChime->value,
        'custom_media_id' => null,
    ]);
});

test('a super admin can disable notification sounds globally', function () {
    $this->actingAs(superAdmin())
        ->put('/tasks/settings/notification-sound', [
            'enabled' => false,
            'source' => 'system',
            'system_sound' => NotificationSystemSound::ClassicChime->value,
        ])
        ->assertRedirect();

    expect(app(TaskManagementNotificationSoundService::class)->playbackConfig())
        ->toMatchArray([
            'enabled' => false,
            'source' => null,
            'system_sound' => null,
            'url' => null,
        ]);
});

test('a super admin can save a system notification sound selection', function () {
    $this->actingAs(superAdmin())
        ->put('/tasks/settings/notification-sound', [
            'enabled' => true,
            'source' => 'system',
            'system_sound' => NotificationSystemSound::DigitalAlert->value,
        ])
        ->assertRedirect();

    expect(AppSetting::payload('task_management', 'notification_sound')['system_sound'])
        ->toBe(NotificationSystemSound::DigitalAlert->value);

    expect(app(TaskManagementNotificationSoundService::class)->playbackConfig())
        ->toMatchArray([
            'enabled' => true,
            'source' => 'system',
            'system_sound' => NotificationSystemSound::DigitalAlert->value,
            'url' => NotificationSystemSound::DigitalAlert->publicUrl(),
        ]);
});

test('an invalid system notification sound is rejected', function () {
    $this->actingAs(superAdmin())
        ->from('/tasks/settings')
        ->put('/tasks/settings/notification-sound', [
            'enabled' => true,
            'source' => 'system',
            'system_sound' => 'not-a-real-sound',
        ])
        ->assertRedirect('/tasks/settings')
        ->assertSessionHasErrors('system_sound');
});

test('a valid mp3 custom notification sound can be uploaded', function () {
    $this->actingAs(superAdmin())
        ->post('/tasks/settings/notification-sound/custom', [
            'sound' => UploadedFile::fake()->create('alert.mp3', 120, 'audio/mpeg'),
        ])
        ->assertRedirect();

    $payload = AppSetting::payload('task_management', 'notification_sound');

    expect($payload['source'])->toBe('custom')
        ->and($payload['custom_media_id'])->not->toBeNull()
        ->and(NotificationSoundAsset::singleton()->customSoundMedia())->not->toBeNull();
});

test('a valid wav custom notification sound can be uploaded', function () {
    $this->actingAs(superAdmin())
        ->post('/tasks/settings/notification-sound/custom', [
            'sound' => UploadedFile::fake()->create('alert.wav', 120, 'audio/wav'),
        ])
        ->assertRedirect();

    expect(NotificationSoundAsset::singleton()->customSoundMedia()?->file_name)->toBe('alert.wav');
});

test('a valid ogg custom notification sound can be uploaded', function () {
    $this->actingAs(superAdmin())
        ->post('/tasks/settings/notification-sound/custom', [
            'sound' => UploadedFile::fake()->create('alert.ogg', 120, 'audio/ogg'),
        ])
        ->assertRedirect();

    expect(NotificationSoundAsset::singleton()->customSoundMedia()?->file_name)->toBe('alert.ogg');
});

test('an invalid custom notification sound type is rejected', function () {
    $this->actingAs(superAdmin())
        ->from('/tasks/settings')
        ->post('/tasks/settings/notification-sound/custom', [
            'sound' => UploadedFile::fake()->create('alert.txt', 10, 'text/plain'),
        ])
        ->assertRedirect('/tasks/settings')
        ->assertSessionHasErrors('sound');
});

test('an oversized custom notification sound is rejected', function () {
    $this->actingAs(superAdmin())
        ->from('/tasks/settings')
        ->post('/tasks/settings/notification-sound/custom', [
            'sound' => UploadedFile::fake()->create('alert.mp3', 6000, 'audio/mpeg'),
        ])
        ->assertRedirect('/tasks/settings')
        ->assertSessionHasErrors('sound');
});

test('uploading a new custom sound replaces the previous custom sound only', function () {
    $service = app(TaskManagementNotificationSoundService::class);

    $this->actingAs(superAdmin())
        ->post('/tasks/settings/notification-sound/custom', [
            'sound' => UploadedFile::fake()->create('first.mp3', 100, 'audio/mpeg'),
        ]);

    $firstId = $service->customMedia()?->id;

    $this->actingAs(superAdmin())
        ->post('/tasks/settings/notification-sound/custom', [
            'sound' => UploadedFile::fake()->create('second.mp3', 100, 'audio/mpeg'),
        ]);

    $secondId = $service->customMedia()?->id;

    expect($firstId)->not->toBeNull()
        ->and($secondId)->not->toBe($firstId)
        ->and(Media::query()->find($firstId))->toBeNull()
        ->and(NotificationSoundAsset::singleton()->getMedia('custom_sound'))->toHaveCount(1);
});

test('deleting the custom notification sound clears the reference and switches back to system', function () {
    $this->actingAs(superAdmin())
        ->post('/tasks/settings/notification-sound/custom', [
            'sound' => UploadedFile::fake()->create('alert.mp3', 100, 'audio/mpeg'),
        ]);

    $this->actingAs(superAdmin())
        ->delete('/tasks/settings/notification-sound/custom')
        ->assertRedirect();

    $payload = AppSetting::payload('task_management', 'notification_sound');

    expect($payload['source'])->toBe('system')
        ->and($payload['custom_media_id'])->toBeNull()
        ->and(NotificationSoundAsset::singleton()->customSoundMedia())->toBeNull();
});

test('custom notification sound uploads do not delete unrelated media', function () {
    Storage::fake('public');

    $deliverable = Deliverable::factory()->create();
    $deliverable->addMedia(UploadedFile::fake()->image('hero.jpg'))->toMediaCollection('proofs');

    $proofId = $deliverable->fresh()->getFirstMedia('proofs')?->id;
    expect($proofId)->not->toBeNull();

    $this->actingAs(superAdmin())
        ->post('/tasks/settings/notification-sound/custom', [
            'sound' => UploadedFile::fake()->create('alert.mp3', 100, 'audio/mpeg'),
        ])
        ->assertRedirect();

    expect(Media::query()->find($proofId))->not->toBeNull()
        ->and($deliverable->fresh()->getMedia('proofs'))->toHaveCount(1);
});

test('internal staff can stream the active custom notification sound', function () {
    $this->actingAs(superAdmin())
        ->post('/tasks/settings/notification-sound/custom', [
            'sound' => UploadedFile::fake()->create('alert.mp3', 100, 'audio/mpeg'),
        ]);

    $employee = employeeWith(Ability::AccessTasks);

    $this->actingAs($employee->user)
        ->get('/tasks/notification-sound/custom')
        ->assertOk();
});

test('proof retention settings remain unchanged when notification sound settings are updated', function () {
    app(TaskManagementNotificationSoundService::class);
    app(TaskManagementRetentionService::class)->writePolicy(true, 15);

    $this->actingAs(superAdmin())
        ->put('/tasks/settings/notification-sound', [
            'enabled' => false,
            'source' => 'system',
            'system_sound' => NotificationSystemSound::SoftBell->value,
        ])
        ->assertRedirect();

    expect(AppSetting::payload('task_management', 'proof_retention'))->toMatchArray([
        'enabled' => true,
        'days' => 15,
    ]);
});
