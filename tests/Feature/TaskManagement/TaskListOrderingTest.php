<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;

test('the tasks list shows active work before completed work', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);

    Task::factory()->create([
        'title' => 'Completed urgent',
        'status' => TaskStatus::Completed,
        'priority' => TaskPriority::Urgent,
        'completed_at' => now(),
    ]);

    Task::factory()->open()->create([
        'title' => 'Open low priority',
        'priority' => TaskPriority::Low,
    ]);

    $this->actingAs($manager->user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.data.0.title', 'Open low priority')
            ->where('tasks.data.1.title', 'Completed urgent'));
});

test('active tasks on the list are ordered by priority then nearest due date', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);

    Task::factory()->open()->create([
        'title' => 'Due later',
        'priority' => TaskPriority::Normal,
        'due_at' => now()->addDays(10),
    ]);

    Task::factory()->open()->create([
        'title' => 'Due sooner',
        'priority' => TaskPriority::Normal,
        'due_at' => now()->addDays(2),
    ]);

    Task::factory()->open()->create([
        'title' => 'Urgent next',
        'priority' => TaskPriority::Urgent,
        'due_at' => now()->addDays(5),
    ]);

    $this->actingAs($manager->user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.data.0.title', 'Urgent next')
            ->where('tasks.data.1.title', 'Due sooner')
            ->where('tasks.data.2.title', 'Due later'));
});

test('completed tasks on the list are ordered by newest completion first', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);

    Task::factory()->open()->create(['title' => 'Still open']);

    Task::factory()->create([
        'title' => 'Completed older',
        'status' => TaskStatus::Completed,
        'completed_at' => now()->subDays(3),
    ]);

    Task::factory()->create([
        'title' => 'Completed newer',
        'status' => TaskStatus::Completed,
        'completed_at' => now()->subDay(),
    ]);

    $this->actingAs($manager->user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.data.0.title', 'Still open')
            ->where('tasks.data.1.title', 'Completed newer')
            ->where('tasks.data.2.title', 'Completed older'));
});

test('task list pagination keeps completed work off earlier pages when enough active work exists', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);

    Task::factory()->open()->create(['title' => 'Active one', 'due_at' => now()->addDay()]);
    Task::factory()->open()->create(['title' => 'Active two', 'due_at' => now()->addDays(2)]);
    Task::factory()->open()->create(['title' => 'Active three', 'due_at' => now()->addDays(3)]);

    Task::factory()->create([
        'title' => 'Completed one',
        'status' => TaskStatus::Completed,
        'completed_at' => now(),
    ]);

    Task::factory()->create([
        'title' => 'Completed two',
        'status' => TaskStatus::Completed,
        'completed_at' => now()->subDay(),
    ]);

    $this->actingAs($manager->user)
        ->get('/tasks?per_page=2')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.current_page', 1)
            ->where('tasks.last_page', 3)
            ->where('tasks.data.0.title', 'Active one')
            ->where('tasks.data.1.title', 'Active two'));

    $this->actingAs($manager->user)
        ->get('/tasks?per_page=2&page=2')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.data.0.title', 'Active three')
            ->where('tasks.data.1.title', 'Completed one'));
});

test('task list ordering works together with status and search filters', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $project = Project::factory()->create();

    Task::factory()->create([
        'tm_project_id' => $project->id,
        'title' => 'Filtered completed older',
        'status' => TaskStatus::Completed,
        'completed_at' => now()->subDays(2),
    ]);

    Task::factory()->create([
        'tm_project_id' => $project->id,
        'title' => 'Filtered completed newer',
        'status' => TaskStatus::Completed,
        'completed_at' => now(),
    ]);

    Task::factory()->open()->create([
        'tm_project_id' => $project->id,
        'title' => 'Filtered active',
    ]);

    $this->actingAs($manager->user)
        ->get('/tasks?status=completed&project='.$project->id.'&search=Filtered')
        ->assertInertia(fn ($page) => $page
            ->has('tasks.data', 2)
            ->where('tasks.data.0.title', 'Filtered completed newer')
            ->where('tasks.data.1.title', 'Filtered completed older'));

    $this->actingAs($manager->user)
        ->get('/tasks?project='.$project->id.'&search=Filtered')
        ->assertInertia(fn ($page) => $page
            ->has('tasks.data', 3)
            ->where('tasks.data.0.title', 'Filtered active')
            ->where('tasks.data.1.title', 'Filtered completed newer')
            ->where('tasks.data.2.title', 'Filtered completed older'));
});
