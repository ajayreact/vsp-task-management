<?php

use App\Modules\Core\Enums\Ability;

/*
|--------------------------------------------------------------------------
| Sidebar destinations
|--------------------------------------------------------------------------
|
| The left nav points at the prefixed routes the modules actually own. The
| unprefixed paths people type in the address bar must still get there.
|
*/

test('every sidebar management screen exists', function (string $url) {
    $this->actingAs(superAdmin())->get($url)->assertOk();
})->with([
    '/admin/employees',
    '/admin/departments',
    '/admin/attendance',
    '/admin/attendance/offices',
    '/tasks',
    '/tasks/board',
    '/tasks/projects',
    '/tasks/clients',
    '/tasks/availability',
    '/tasks/timesheets',
    '/tasks/workload',
]);

test('short aliases send people to the real screens instead of a 404', function (string $from, string $to) {
    $this->actingAs(staffWith(Ability::AccessTasks, Ability::ViewEmployees, Ability::ViewDepartments, Ability::ViewRoles, Ability::ViewCompanies, Ability::ViewProjects))
        ->get($from)
        ->assertRedirect($to);
})->with([
    ['/employees', '/admin/employees'],
    ['/departments', '/admin/departments'],
    ['/roles', '/admin/roles'],
    ['/clients', '/tasks/clients'],
    ['/work-clients', '/tasks/clients'],
    ['/companies', '/tasks/clients'],
    ['/tasks/companies', '/tasks/clients'],
    ['/projects', '/tasks/projects'],
    ['/tasks/open-board', '/tasks/board'],
]);

test('guests hitting a short alias are sent to login', function () {
    $this->get('/employees')->assertRedirect('/login');
});
