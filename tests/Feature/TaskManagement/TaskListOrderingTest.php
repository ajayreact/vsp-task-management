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

test('active tasks on the list are ordered by newest created first', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);

    Task::factory()->open()->create([
        'title' => 'Older active',
        'created_at' => now()->subDays(3),
    ]);

    Task::factory()->open()->create([
        'title' => 'Newer active',
        'created_at' => now()->subDay(),
    ]);

    Task::factory()->create([
        'title' => 'In progress recent',
        'status' => TaskStatus::InProgress,
        'created_at' => now(),
    ]);

    $this->actingAs($manager->user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.data.0.title', 'In progress recent')
            ->where('tasks.data.1.title', 'Newer active')
            ->where('tasks.data.2.title', 'Older active'));
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

test('mixed active statuses stay together above completed tasks', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);

    Task::factory()->create([
        'title' => 'Task E completed yesterday',
        'status' => TaskStatus::Completed,
        'created_at' => now()->subDays(2),
        'completed_at' => now()->subDay(),
    ]);

    Task::factory()->create([
        'title' => 'Task D completed today',
        'status' => TaskStatus::Completed,
        'created_at' => now(),
        'completed_at' => now(),
    ]);

    Task::factory()->create([
        'title' => 'Task C under review yesterday',
        'status' => TaskStatus::InReview,
        'created_at' => now()->subDay(),
    ]);

    Task::factory()->create([
        'title' => 'Task B assigned today',
        'status' => TaskStatus::Assigned,
        'created_at' => now()->subHours(2),
    ]);

    Task::factory()->create([
        'title' => 'Task A in progress recently',
        'status' => TaskStatus::InProgress,
        'created_at' => now(),
    ]);

    $this->actingAs($manager->user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.data.0.title', 'Task A in progress recently')
            ->where('tasks.data.1.title', 'Task B assigned today')
            ->where('tasks.data.2.title', 'Task C under review yesterday')
            ->where('tasks.data.3.title', 'Task D completed today')
            ->where('tasks.data.4.title', 'Task E completed yesterday'));
});

test('task list pagination keeps completed work off earlier pages when enough active work exists', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);

    Task::factory()->open()->create(['title' => 'Active oldest', 'created_at' => now()->subDays(3)]);
    Task::factory()->open()->create(['title' => 'Active middle', 'created_at' => now()->subDays(2)]);
    Task::factory()->open()->create(['title' => 'Active newest', 'created_at' => now()->subDay()]);

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
            ->where('tasks.data.0.title', 'Active newest')
            ->where('tasks.data.1.title', 'Active middle'));

    $this->actingAs($manager->user)
        ->get('/tasks?per_page=2&page=2')
        ->assertInertia(fn ($page) => $page
            ->where('tasks.data.0.title', 'Active oldest')
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
        'title' => 'Filtered active newest',
        'created_at' => now(),
    ]);

    Task::factory()->open()->create([
        'tm_project_id' => $project->id,
        'title' => 'Filtered active older',
        'created_at' => now()->subDay(),
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
            ->has('tasks.data', 4)
            ->where('tasks.data.0.title', 'Filtered active newest')
            ->where('tasks.data.1.title', 'Filtered active older')
            ->where('tasks.data.2.title', 'Filtered completed newer')
            ->where('tasks.data.3.title', 'Filtered completed older'));
});

test('a filtered non-completed status list orders by newest created first', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);

    Task::factory()->create([
        'title' => 'Older in progress',
        'status' => TaskStatus::InProgress,
        'created_at' => now()->subDays(2),
    ]);

    Task::factory()->create([
        'title' => 'Newer in progress',
        'status' => TaskStatus::InProgress,
        'created_at' => now(),
    ]);

    Task::factory()->create([
        'title' => 'Completed should be excluded',
        'status' => TaskStatus::Completed,
        'created_at' => now(),
    ]);

    $this->actingAs($manager->user)
        ->get('/tasks?status=in_progress')
        ->assertInertia(fn ($page) => $page
            ->has('tasks.data', 2)
            ->where('tasks.data.0.title', 'Newer in progress')
            ->where('tasks.data.1.title', 'Older in progress'));
});
