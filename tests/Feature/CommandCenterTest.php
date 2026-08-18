<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use Spatie\Permission\Models\Role;

test('a manager sees agency-wide task counts on the command center', function () {
    $manager = User::factory()->create()->syncRoles(
        Role::findOrCreate(SystemRole::Manager->value, 'web'),
    );
    $manager->syncPermissions([
        Ability::AccessTasks->value,
        Ability::ViewAllTasks->value,
        Ability::ViewWorkload->value,
        Ability::ManageTasks->value,
    ]);

    Task::factory()->open()->create();
    Task::factory()->create(['status' => TaskStatus::InProgress]);
    Task::factory()->create(['status' => TaskStatus::InReview]);

    $this->actingAs($manager)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('snapshot.scope', 'agency')
            ->where('snapshot.modules.tasks', true)
            ->where('snapshot.kpis.0.key', 'in_progress')
            ->where('snapshot.kpis.0.value', 1)
            ->where('snapshot.kpis.1.value', 1)
            ->where('snapshot.kpis.2.value', 1)
            ->where('snapshot.can.create_task', true));
});

test('an employee only sees their own in-progress work', function () {
    $mine = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $theirs = employeeWith(Ability::AccessTasks);

    Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $mine->id,
    ]);
    Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $theirs->id,
    ]);
    Task::factory()->awaitingAcceptance($mine)->create();

    $this->actingAs($mine->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('snapshot.scope', 'personal')
            ->where('snapshot.modules.tasks', true)
            ->where('snapshot.kpis.0.value', 1)
            ->has('snapshot.actions', 1));
});

test('a tasks user sees task metrics on the command center', function () {
    $employee = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('snapshot.modules.tasks', true)
            ->has('snapshot.kpis', 4)
            ->where('snapshot.kpis.0.key', 'in_progress'));
});

test('a user without task access sees no task metrics', function () {
    $user = staffWith();
    Task::factory()->open()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('snapshot.modules.tasks', false)
            ->has('snapshot.kpis', 0)
            ->has('snapshot.actions', 0)
            ->where('snapshot.timer', null)
            ->where('snapshot.can.create_task', false));
});
