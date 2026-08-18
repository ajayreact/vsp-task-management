<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Task;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('operations head has full task management access via gate', function () {
    $ops = User::factory()->create()->syncRoles(SystemRole::SuperAdmin->value);

    expect($ops->can(Ability::ManageTasks->value))->toBeTrue()
        ->and($ops->can(Ability::AssignTasks->value))->toBeTrue()
        ->and($ops->can(Ability::ManageEmployees->value))->toBeTrue()
        ->and($ops->can(Ability::ManageCompanies->value))->toBeTrue()
        ->and($ops->can(Ability::ManageProjects->value))->toBeTrue()
        ->and($ops->can(Ability::ManageDepartments->value))->toBeTrue();

    $this->actingAs($ops)
        ->get('/tasks/create')
        ->assertOk();
});

test('team leads can create and assign tasks but not manage org structure', function () {
    $lead = User::factory()->create()->syncRoles(SystemRole::TeamLead->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($lead->can(Ability::ManageTasks->value))->toBeTrue()
        ->and($lead->can(Ability::AssignTasks->value))->toBeTrue()
        ->and($lead->can(Ability::ViewAllTasks->value))->toBeTrue()
        ->and($lead->can(Ability::ReviewDeliverables->value))->toBeTrue()
        ->and($lead->can(Ability::ViewWorkload->value))->toBeTrue()
        ->and($lead->can(Ability::ManageEmployees->value))->toBeFalse()
        ->and($lead->can(Ability::ManageDepartments->value))->toBeFalse()
        ->and($lead->can(Ability::ManageCompanies->value))->toBeFalse()
        ->and($lead->can(Ability::ManageProjects->value))->toBeFalse()
        ->and($lead->can(Ability::ManageRoles->value))->toBeFalse();

    $this->actingAs($lead)
        ->get('/tasks/create')
        ->assertOk();

    $task = Task::factory()->create(['created_by_user_id' => $lead->id]);
    $assignee = employeeWith(Ability::AccessTasks);

    $this->actingAs($lead)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $assignee->id])
        ->assertRedirect();
});

test('employees cannot create or assign tasks', function () {
    $employee = User::factory()->create()->syncRoles(SystemRole::Employee->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($employee->can(Ability::ManageTasks->value))->toBeFalse()
        ->and($employee->can(Ability::AssignTasks->value))->toBeFalse()
        ->and($employee->can(Ability::ViewAllTasks->value))->toBeFalse()
        ->and($employee->can(Ability::AccessTasks->value))->toBeTrue();

    $this->actingAs($employee)
        ->get('/tasks/create')
        ->assertForbidden();

    $task = Task::factory()->create();

    $this->actingAs($employee)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => employeeWith(Ability::AccessTasks)->id])
        ->assertForbidden();

    $this->actingAs($employee)
        ->post("/tasks/{$task->id}/publish")
        ->assertForbidden();
});
