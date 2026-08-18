<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\AppSetting;
use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->withoutVite();
});

test('a super admin can view the settings page', function () {
    $this->actingAs(superAdmin())
        ->get('/tasks/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/settings/index')
            ->where('retention.enabled', false)
            ->where('retention.days', null)
            ->where('notificationSound.enabled', true));
});

test('a normal employee cannot view the settings page', function () {
    $this->actingAs(employeeWith(Ability::AccessTasks)->user)
        ->get('/tasks/settings')
        ->assertForbidden();
});

test('a team lead cannot view or update the retention policy', function () {
    $lead = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $lead->user->syncRoles(Role::findOrCreate(SystemRole::TeamLead->value, 'web'));

    $this->actingAs($lead->user)
        ->get('/tasks/settings')
        ->assertForbidden();

    $this->actingAs($lead->user)
        ->put('/tasks/settings', ['enabled' => true, 'days' => 7])
        ->assertForbidden();
});

test('a super admin can disable retention', function () {
    app(TaskManagementRetentionService::class)->writePolicy(true, 7);

    $this->actingAs(superAdmin())
        ->put('/tasks/settings', ['enabled' => false, 'days' => 7])
        ->assertRedirect();

    expect(AppSetting::payload('task_management', 'proof_retention'))->toMatchArray([
        'enabled' => false,
        'days' => null,
    ]);
});

test('a super admin can enable seven day retention', function () {
    $this->actingAs(superAdmin())
        ->put('/tasks/settings', ['enabled' => true, 'days' => 7])
        ->assertRedirect();

    expect(AppSetting::payload('task_management', 'proof_retention'))->toMatchArray([
        'enabled' => true,
        'days' => 7,
    ]);
});

test('a super admin can enable fifteen day retention', function () {
    $this->actingAs(superAdmin())
        ->put('/tasks/settings', ['enabled' => true, 'days' => 15])
        ->assertRedirect();

    expect(AppSetting::payload('task_management', 'proof_retention'))->toMatchArray([
        'enabled' => true,
        'days' => 15,
    ]);
});

test('a super admin can enable thirty day retention', function () {
    $this->actingAs(superAdmin())
        ->put('/tasks/settings', ['enabled' => true, 'days' => 30])
        ->assertRedirect();

    expect(AppSetting::payload('task_management', 'proof_retention'))->toMatchArray([
        'enabled' => true,
        'days' => 30,
    ]);
});

test('a super admin can set a valid custom retention period', function () {
    $this->actingAs(superAdmin())
        ->put('/tasks/settings', ['enabled' => true, 'days' => 42])
        ->assertRedirect();

    expect(AppSetting::payload('task_management', 'proof_retention'))->toMatchArray([
        'enabled' => true,
        'days' => 42,
    ]);
});

test('a custom value of zero is rejected', function () {
    $this->actingAs(superAdmin())
        ->from('/tasks/settings')
        ->put('/tasks/settings', ['enabled' => true, 'days' => 0])
        ->assertRedirect('/tasks/settings')
        ->assertSessionHasErrors('days');
});

test('a value greater than 3650 is rejected', function () {
    $this->actingAs(superAdmin())
        ->from('/tasks/settings')
        ->put('/tasks/settings', ['enabled' => true, 'days' => 3651])
        ->assertRedirect('/tasks/settings')
        ->assertSessionHasErrors('days');
});

test('enabling retention without days is rejected', function () {
    $this->actingAs(superAdmin())
        ->from('/tasks/settings')
        ->put('/tasks/settings', ['enabled' => true])
        ->assertRedirect('/tasks/settings')
        ->assertSessionHasErrors('days');
});

test('the retention service reads the setting saved from the settings page', function () {
    $this->actingAs(superAdmin())
        ->put('/tasks/settings', ['enabled' => true, 'days' => 15])
        ->assertRedirect();

    expect(app(TaskManagementRetentionService::class)->policy())->toMatchArray([
        'enabled' => true,
        'days' => 15,
    ]);
});
