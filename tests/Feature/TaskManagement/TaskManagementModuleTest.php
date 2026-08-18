<?php

use App\Modules\Core\Enums\Ability;

test('guests are redirected away from task management', function () {
    $this->get('/tasks')->assertRedirect('/login');
});

test('the module is closed to users without the access ability', function () {
    $this->actingAs(staffWith())
        ->get('/tasks')
        ->assertForbidden();
});

test('staff with the access ability reach the task list', function () {
    $this->actingAs(staffWith(Ability::AccessTasks))
        ->get('/tasks')
        ->assertOk();
});

test('task management routes are namespaced under /tasks', function () {
    expect(route('tasks.index', absolute: false))->toBe('/tasks')
        ->and(route('tasks.board', absolute: false))->toBe('/tasks/board')
        ->and(route('tasks.projects.index', absolute: false))->toBe('/tasks/projects');
});

test('literal segments are not mistaken for task ids', function () {
    $this->actingAs(staffWith(Ability::AccessTasks, Ability::ViewProjects))
        ->get('/tasks/projects')
        ->assertOk();
});
