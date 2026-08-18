<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use Database\Seeders\Core\RolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

test('guests are redirected to login', function (string $url) {
    $this->get($url)->assertRedirect('/login');
})->with(['/admin/employees', '/admin/departments', '/admin/roles']);

test('a signed-in user without permissions is refused', function (string $url) {
    $this->actingAs(staffWith())->get($url)->assertForbidden();
})->with(['/admin/employees', '/admin/departments', '/admin/roles']);

test('each ability opens only its own screen', function () {
    $this->actingAs(staffWith(Ability::ViewEmployees))
        ->get('/admin/employees')
        ->assertOk();

    $this->actingAs(staffWith(Ability::ViewEmployees))
        ->get('/admin/roles')
        ->assertForbidden();
});

test('super admin passes checks it holds no permission row for', function () {
    $admin = superAdmin();

    expect($admin->permissions)->toBeEmpty();

    $this->actingAs($admin)->get('/admin/roles')->assertOk();
});

test('employees cannot open administration screens', function (string $url) {
    $employee = User::factory()->create()->syncRoles(SystemRole::Employee->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    expect($employee->can(Ability::ViewEmployees->value))->toBeFalse()
        ->and($employee->can(Ability::ViewDepartments->value))->toBeFalse();

    $this->actingAs($employee)->get($url)->assertForbidden();
})->with(['/admin/employees', '/admin/departments']);

test('admin users can open employee and department screens', function (string $url) {
    $admin = User::factory()->create()->syncRoles(SystemRole::Admin->value);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($admin)->get($url)->assertOk();
})->with(['/admin/employees', '/admin/departments']);
