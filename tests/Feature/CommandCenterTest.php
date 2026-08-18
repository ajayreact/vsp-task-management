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
            ->has('snapshot.overview', 9)
            ->where('snapshot.overview.0.key', 'total')
            ->where('snapshot.overview.1.key', 'in_progress')
            ->where('snapshot.overview.1.count', 1)
            ->where('snapshot.overview.3.key', 'in_review')
            ->where('snapshot.overview.3.count', 1)
            ->where('snapshot.team.timers.count', 0)
            ->has('snapshot.attention.overdue')
            ->has('snapshot.activity')
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
            ->where('snapshot.overview.0.key', 'in_progress')
            ->where('snapshot.overview.0.count', 1)
            ->where('snapshot.team', null)
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
            ->has('snapshot.overview')
            ->where('snapshot.overview.0.key', 'in_progress'));
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
            ->has('snapshot.overview', 0)
            ->has('snapshot.actions', 0)
            ->where('snapshot.timer', null)
            ->where('snapshot.can.create_task', false));
});

test('overdue tasks link to the filtered task list', function () {
    $manager = User::factory()->create()->syncRoles(
        Role::findOrCreate(SystemRole::Manager->value, 'web'),
    );
    $manager->syncPermissions([
        Ability::AccessTasks->value,
        Ability::ViewAllTasks->value,
    ]);

    Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'due_at' => now()->subDay(),
    ]);

    $this->actingAs($manager)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('snapshot.overview', fn ($overview) => collect($overview)->contains(
                fn ($stat) => $stat['key'] === 'overdue'
                    && $stat['count'] === 1
                    && str_contains($stat['href'], 'overdue=1'),
            )));
});
