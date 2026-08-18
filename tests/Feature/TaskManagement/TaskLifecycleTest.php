<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;

/*
|--------------------------------------------------------------------------
| Task CRUD, visibility and the state machine over HTTP
|--------------------------------------------------------------------------
*/

test('a task is created as a draft with its creator recorded', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Design the launch deck',
            'type' => 'design',
            'priority' => 'high',
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    expect($task->status)->toBe(TaskStatus::Draft)
        ->and($task->created_by_user_id)->toBe($author->user->id)
        ->and($task->assigned_employee_id)->toBeNull()
        ->and($task->statusHistory()->sole()->to_status)->toBe(TaskStatus::Draft);
});

test('tasks cannot be raised against a closed project', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->completed()->create();

    $this->actingAs($author->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Too late',
            'type' => 'design',
            'priority' => 'normal',
        ])
        ->assertSessionHasErrors('tm_project_id');
});

test('the form cannot set status on create, only the workflow can', function () {
    $author = employeeWith(Ability::AccessTasks, Ability::ManageTasks);
    $project = Project::factory()->create();

    $this->actingAs($author->user)->post('/tasks', [
        'tm_project_id' => $project->id,
        'title' => 'Sneaky',
        'type' => 'design',
        'priority' => 'normal',
        'status' => TaskStatus::Completed->value,
    ]);

    $task = Task::query()->sole();

    expect($task->status)->toBe(TaskStatus::Draft);
});

test('creating a task with an assignee runs the workflow and assigns the employee', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ManageTasks, Ability::AssignTasks, Ability::ViewAllTasks);
    $employee = employeeWith(Ability::AccessTasks);
    $project = Project::factory()->create();

    $this->actingAs($manager->user)
        ->post('/tasks', [
            'tm_project_id' => $project->id,
            'title' => 'Launch creative',
            'type' => 'design',
            'priority' => 'normal',
            'assigned_employee_id' => $employee->id,
        ])
        ->assertRedirect();

    $task = Task::query()->sole();

    expect($task->status)->toBe(TaskStatus::Assigned)
        ->and($task->assigned_employee_id)->toBe($employee->id);
});

test('someone without view_all sees only their own tasks', function () {
    $employee = employeeWith(Ability::AccessTasks);
    Task::factory()->acceptedBy($employee)->create(['title' => 'Mine']);
    Task::factory()->create(['title' => 'Someone elses']);

    $this->actingAs($employee->user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->has('tasks.data', 1)
            ->where('tasks.data.0.title', 'Mine')
            ->where('filters.scope', 'mine'));
});

test('the scope filter is ignored for someone who cannot see all tasks', function () {
    $employee = employeeWith(Ability::AccessTasks);
    Task::factory()->create(['title' => 'Someone elses']);

    $this->actingAs($employee->user)
        ->get('/tasks?scope=all')
        ->assertInertia(fn ($page) => $page->has('tasks.data', 0)->where('filters.scope', 'mine'));
});

test('a manager sees everything and can narrow to unassigned work', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $other = employeeWith(Ability::AccessTasks);

    Task::factory()->acceptedBy($other)->create(['title' => 'Taken']);
    Task::factory()->open()->create(['title' => 'Free']);

    $this->actingAs($manager->user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page->has('tasks.data', 2));

    $this->actingAs($manager->user)
        ->get('/tasks?scope=unassigned')
        ->assertInertia(fn ($page) => $page->has('tasks.data', 1)->where('tasks.data.0.title', 'Free'));
});

test('an unrelated task is hidden from someone who cannot see all tasks', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $other = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($other)->create();

    $this->actingAs($employee->user)
        ->get("/tasks/{$task->id}")
        ->assertForbidden();
});

test('an open task is visible to everyone so that it can be picked up', function () {
    $this->withoutVite();

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->open()->create();

    $this->actingAs($employee->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('can.claim', true));
});

test('the assignee can move an in-progress task into review', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/status", ['status' => TaskStatus::InReview->value])
        ->assertRedirect();

    expect($task->refresh()->status)->toBe(TaskStatus::InReview);
});

test('an illegal jump through the lifecycle is refused', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/status", ['status' => TaskStatus::Completed->value])
        ->assertSessionHas('error');

    expect($task->refresh()->status)->toBe(TaskStatus::InProgress);
});

test('a bystander cannot push someone elses task along', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $bystander = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $task = Task::factory()->acceptedBy($employee)->create();

    $this->actingAs($bystander->user)
        ->post("/tasks/{$task->id}/status", ['status' => TaskStatus::InProgress->value])
        ->assertForbidden();
});

test('a task that is under way cannot be deleted', function () {
    $employee = employeeWith(Ability::AccessTasks, Ability::ManageTasks, Ability::ViewAllTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->delete("/tasks/{$task->id}")
        ->assertForbidden();

    expect(Task::query()->count())->toBe(1);
});

test('the detail screen offers only the transitions the task can make', function () {
    $this->withoutVite();

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create();

    $this->actingAs($employee->user)
        ->get("/tasks/{$task->id}")
        ->assertInertia(fn ($page) => $page
            ->has('allowedTransitions', 1)
            ->where('allowedTransitions.0.value', TaskStatus::InReview->value));
});
