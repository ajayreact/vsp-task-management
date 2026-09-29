<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/**
 * An employee holding exactly one seeded system role, as production staff do.
 */
function recruiterOpsEmployeeWithRole(SystemRole $role): Employee
{
    $employee = Employee::factory()->create();
    $employee->user->syncRoles($role->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $employee;
}

test('guests are redirected to login', function () {
    $this->get('/recruiter')->assertRedirect('/login');
});

test('a signed-in user without recruiter access is refused', function () {
    $this->actingAs(staffWith())->get('/recruiter')->assertForbidden();
});

test('digital marketing staff do not reach recruiter operations', function (SystemRole $role) {
    $this->actingAs(recruiterOpsEmployeeWithRole($role)->user)
        ->get('/recruiter')
        ->assertForbidden();
})->with([
    'employee' => SystemRole::Employee,
    'team lead' => SystemRole::TeamLead,
    'manager' => SystemRole::Manager,
]);

test('task management access alone does not open recruiter operations', function () {
    $this->actingAs(staffWith(Ability::AccessTasks, Ability::ViewAllTasks, Ability::ManageTasks))
        ->get('/recruiter')
        ->assertForbidden();
});

test('recruiters and recruiter leads reach the recruiter dashboard', function (SystemRole $role) {
    $this->actingAs(recruiterOpsEmployeeWithRole($role)->user)
        ->get('/recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/dashboard')
            ->where('hasEmployeeProfile', true));
})->with([
    'recruiter' => SystemRole::Recruiter,
    'recruiter lead' => SystemRole::RecruiterLead,
]);

test('admin reaches recruiter operations through its full ability set', function () {
    $admin = User::factory()->create()->syncRoles(SystemRole::Admin->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($admin)
        ->get('/recruiter')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('RecruiterOperations/dashboard')
            ->where('hasEmployeeProfile', false));
});

test('super admin reaches recruiter operations without holding the permission row', function () {
    $admin = superAdmin();

    expect($admin->permissions)->toBeEmpty();

    $this->actingAs($admin)->get('/recruiter')->assertOk();
});

test('recruiters do not gain digital marketing task management', function (string $url) {
    $this->actingAs(recruiterOpsEmployeeWithRole(SystemRole::Recruiter)->user)
        ->get($url)
        ->assertForbidden();
})->with(['/tasks', '/tasks/board', '/tasks/todos', '/tasks/projects', '/tasks/clients']);

test('recruiter leads do not gain digital marketing task management', function () {
    $this->actingAs(recruiterOpsEmployeeWithRole(SystemRole::RecruiterLead)->user)
        ->get('/tasks')
        ->assertForbidden();
});

test('recruiters cannot open administration or finance screens', function (string $url) {
    $this->actingAs(recruiterOpsEmployeeWithRole(SystemRole::Recruiter)->user)
        ->get($url)
        ->assertForbidden();
})->with([
    '/admin/employees',
    '/admin/employees/create',
    '/admin/departments',
    '/admin/roles',
    '/admin/finance',
    '/admin/attendance',
]);

test('recruiters keep access to the shared attendance screens', function (string $url, string $component) {
    $this->actingAs(recruiterOpsEmployeeWithRole(SystemRole::Recruiter)->user)
        ->get($url)
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component($component));
})->with([
    'mark attendance' => ['/attendance/mark', 'Attendance/mark'],
    'wfh requests' => ['/attendance/wfh', 'Attendance/wfh/index'],
]);

test('the recruiter role carries recruiter access only', function () {
    $user = recruiterOpsEmployeeWithRole(SystemRole::Recruiter)->user;

    expect($user->getAllPermissions()->pluck('name')->all())->toBe([Ability::RecruiterAccess->value])
        ->and($user->can(Ability::AccessTasks->value))->toBeFalse()
        ->and($user->can(Ability::ViewEmployees->value))->toBeFalse()
        ->and($user->can(Ability::ManageEmployees->value))->toBeFalse()
        ->and($user->can(Ability::ManageRoles->value))->toBeFalse()
        ->and($user->can('viewMyFinance'))->toBeFalse()
        ->and($user->can(Ability::ViewRecruiterTeam->value))->toBeFalse();
});

test('the recruiter lead role carries every recruiter operations ability and nothing else', function () {
    $user = recruiterOpsEmployeeWithRole(SystemRole::RecruiterLead)->user;

    $expected = [
        'recruiter.access',
        'recruiter.team.view',
        'recruiter.tasks.manage',
        'recruiter.training.manage',
        'recruiter.training.assign',
        'recruiter.assessments.manage',
        'recruiter.assessments.invite',
        'recruiter.assessments.review',
    ];

    expect($user->getAllPermissions()->pluck('name')->sort()->values()->all())->toBe(collect($expected)->sort()->values()->all())
        ->and($user->can(Ability::AccessTasks->value))->toBeFalse();
});

test('digital marketing roles receive no recruiter abilities', function (SystemRole $role) {
    $user = recruiterOpsEmployeeWithRole($role)->user;

    $recruiterPermissions = $user->getAllPermissions()
        ->pluck('name')
        ->filter(fn (string $name) => str_starts_with($name, 'recruiter.'));

    expect($recruiterPermissions)->toBeEmpty();
})->with([
    'employee' => SystemRole::Employee,
    'team lead' => SystemRole::TeamLead,
    'manager' => SystemRole::Manager,
]);

test('navigation data exposes recruiter access only to users who hold it', function () {
    $this->actingAs(recruiterOpsEmployeeWithRole(SystemRole::Recruiter)->user)
        ->get('/recruiter')
        ->assertInertia(fn ($page) => $page
            ->where('auth.permissions', [Ability::RecruiterAccess->value])
            ->where('auth.roles', [SystemRole::Recruiter->value]));

    $this->actingAs(recruiterOpsEmployeeWithRole(SystemRole::Employee)->user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('auth.permissions', fn ($permissions) => ! collect($permissions)->contains(Ability::RecruiterAccess->value)
                && collect($permissions)->contains(Ability::AccessTasks->value)));

    $this->actingAs(superAdmin())
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('auth.permissions', fn ($permissions) => collect($permissions)->contains(Ability::RecruiterAccess->value)));
});
