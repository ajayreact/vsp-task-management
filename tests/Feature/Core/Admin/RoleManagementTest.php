<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use Spatie\Permission\Models\Role;

test('the list groups every ability by area for the editor', function () {
    $this->actingAs(staffWith(Ability::ViewRoles, Ability::ManageRoles))
        ->get('/admin/roles/create')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Core/admin/roles/create')
            ->has('abilities')
        );
});

test('a role is created with the permissions ticked', function () {
    $this->actingAs(staffWith(Ability::ManageRoles))
        ->post('/admin/roles', [
            'name' => 'content-lead',
            'permissions' => [Ability::ViewEmployees->value, Ability::AccessTasks->value],
        ])
        ->assertRedirect('/admin/roles');

    $role = Role::findByName('content-lead');

    expect($role->permissions->pluck('name')->all())
        ->toEqualCanonicalizing([Ability::ViewEmployees->value, Ability::AccessTasks->value]);
});

test('role names are restricted to a slug', function () {
    $this->actingAs(staffWith(Ability::ManageRoles))
        ->post('/admin/roles', ['name' => 'Content Lead', 'permissions' => []])
        ->assertSessionHasErrors('name');
});

test('a permission outside the catalogue is rejected', function () {
    $this->actingAs(staffWith(Ability::ManageRoles))
        ->post('/admin/roles', ['name' => 'content-lead', 'permissions' => ['invented.permission']])
        ->assertSessionHasErrors('permissions.0');
});

test('super admin cannot be edited through the screen', function () {
    $role = Role::findOrCreate(SystemRole::SuperAdmin->value, 'web');

    $this->actingAs(staffWith(Ability::ManageRoles))
        ->get("/admin/roles/{$role->id}/edit")
        ->assertForbidden();
});

test('a built-in role keeps its name but can change permissions', function () {
    $role = Role::findOrCreate(SystemRole::Manager->value, 'web');

    $this->actingAs(staffWith(Ability::ManageRoles))
        ->put("/admin/roles/{$role->id}", ['permissions' => [Ability::ViewEmployees->value]])
        ->assertRedirect('/admin/roles');

    expect($role->refresh()->name)->toBe(SystemRole::Manager->value)
        ->and($role->permissions->pluck('name')->all())->toBe([Ability::ViewEmployees->value]);
});

test('renaming a built-in role is refused', function () {
    $role = Role::findOrCreate(SystemRole::Manager->value, 'web');

    $this->actingAs(staffWith(Ability::ManageRoles))
        ->put("/admin/roles/{$role->id}", ['name' => 'creative-director', 'permissions' => []])
        ->assertSessionHasErrors('name');

    expect($role->refresh()->name)->toBe(SystemRole::Manager->value);
});

test('a built-in role cannot be deleted', function () {
    $role = Role::findOrCreate(SystemRole::Manager->value, 'web');

    $this->actingAs(staffWith(Ability::ManageRoles))
        ->delete("/admin/roles/{$role->id}")
        ->assertForbidden();
});

test('a custom role still held by someone cannot be deleted', function () {
    $role = Role::findOrCreate('content-lead', 'web');
    User::factory()->create()->assignRole($role);

    $this->actingAs(staffWith(Ability::ManageRoles))
        ->delete("/admin/roles/{$role->id}")
        ->assertForbidden();
});

test('an unused custom role can be deleted', function () {
    $role = Role::findOrCreate('content-lead', 'web');

    $this->actingAs(staffWith(Ability::ManageRoles))
        ->delete("/admin/roles/{$role->id}")
        ->assertRedirect('/admin/roles');

    expect(Role::where('name', 'content-lead')->exists())->toBeFalse();
});
