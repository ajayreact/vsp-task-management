<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\AssignmentAction;
use App\Modules\TaskManagement\Enums\AssignmentMode;
use App\Modules\TaskManagement\Enums\AssignmentStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;

function openBoardManager()
{
    return employeeWith(
        Ability::AccessTasks,
        Ability::ManageTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
}

function openBoardWorker()
{
    return employeeWith(Ability::AccessTasks);
}

test('creating a task with open board publishes it for eligible employees to claim', function () {
    $manager = openBoardManager();
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Open board launch task',
            'type' => 'design',
            'priority' => 'normal',
            'publish_to_open_board' => true,
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    expect($task->status)->toBe(TaskStatus::Open)
        ->and($task->assigned_employee_id)->toBeNull()
        ->and($task->assignment_mode)->toBe(AssignmentMode::Open);
});

test('an eligible employee sees an open board task created on the create form', function () {
    $manager = openBoardManager();
    $employee = openBoardWorker();
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Visible on the board',
            'type' => 'design',
            'priority' => 'normal',
            'publish_to_open_board' => true,
        ])
        ->assertRedirect();

    $this->actingAs($employee->user)
        ->get('/tasks/board')
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/board')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.title', 'Visible on the board'));
});

test('an employee can claim an open board task created on the create form', function () {
    $manager = openBoardManager();
    $employee = openBoardWorker();
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Claim me',
            'type' => 'design',
            'priority' => 'normal',
            'publish_to_open_board' => true,
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/claim")
        ->assertRedirect(route('tasks.show', $task));

    $task->refresh();

    expect($task->status)->toBe(TaskStatus::InProgress)
        ->and($task->assigned_employee_id)->toBe($employee->id)
        ->and($task->assignments()->sole()->mode)->toBe(AssignmentAction::Claim)
        ->and($task->assignments()->sole()->status)->toBe(AssignmentStatus::Accepted);
});

test('a claimed open board task no longer appears on the open board', function () {
    $manager = openBoardManager();
    $employee = openBoardWorker();
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Goes away after claim',
            'type' => 'design',
            'priority' => 'normal',
            'publish_to_open_board' => true,
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    $this->actingAs($employee->user)->post("/tasks/{$task->id}/claim")->assertRedirect();

    $this->actingAs($employee->user)
        ->get('/tasks/board')
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/board')
            ->has('tasks.data', 0));
});

test('a user without an employee profile cannot claim an open board task', function () {
    $manager = openBoardManager();
    $project = Project::factory()->create();
    $task = Task::factory()->open()->create([
        'tm_project_id' => $project->id,
        'title' => 'Staff-only claim',
    ]);

    $nonEmployeeUser = \App\Modules\Core\Models\User::factory()->create();

    $this->actingAs($nonEmployeeUser)
        ->post("/tasks/{$task->id}/claim")
        ->assertForbidden();
});

test('creating a task without open board or assignee still saves as a draft', function () {
    $manager = openBoardManager();
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Draft for later',
            'type' => 'design',
            'priority' => 'normal',
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    expect($task->status)->toBe(TaskStatus::Draft)
        ->and($task->assigned_employee_id)->toBeNull();
});

test('creating a task with a direct assignee still uses the existing assignment workflow', function () {
    $manager = openBoardManager();
    $assignee = openBoardWorker();
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Direct handoff',
            'type' => 'design',
            'priority' => 'normal',
            'assigned_employee_id' => $assignee->id,
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    expect($task->status)->toBe(TaskStatus::Assigned)
        ->and($task->assigned_employee_id)->toBe($assignee->id)
        ->and($task->assignment_mode)->toBe(AssignmentMode::Direct)
        ->and($task->assignments()->sole()->status)->toBe(AssignmentStatus::Pending);
});

test('a user without assign permission cannot publish to the open board on create', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Blocked publish',
            'type' => 'design',
            'priority' => 'normal',
            'publish_to_open_board' => true,
        ])
        ->assertForbidden();
});
