<?php

use App\Modules\Core\Models\User;

test('guests are redirected away from task management', function () {
    $this->get('/tasks')->assertRedirect('/login');
});

test('authenticated users can reach task management', function () {
    $this->actingAs(User::factory()->create())
        ->get('/tasks')
        ->assertOk();
});

test('task management routes are namespaced separately from crm', function () {
    expect(route('tasks.dashboard', absolute: false))->toBe('/tasks');
});
