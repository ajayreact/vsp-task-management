<?php

use App\Modules\Core\Enums\Ability;

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
