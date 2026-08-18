<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskSubtask;

beforeEach(function () {
    $this->withoutVite();
});

test('an assignee sees the employee task detail page', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create();

    $this->actingAs($employee->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show-employee')
            ->missing('history')
            ->missing('assignments')
            ->missing('reminders')
            ->missing('recurrence')
            ->where('can.submitProof', false));
});

test('a manager sees the admin task detail page', function () {
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks, Ability::AssignTasks);
    $assignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($assignee)->create();

    $this->actingAs($manager->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show')
            ->has('history')
            ->has('assignments')
            ->has('reminders')
            ->has('recurrence'));
});

test('an employee assigned only to a subtask can open the simplified task page', function () {
    $assignee = employeeWith(Ability::AccessTasks);
    $subtaskAssignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($assignee)->create();
    TaskSubtask::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Resize banner',
        'status' => SubtaskStatus::Pending,
        'assigned_employee_id' => $subtaskAssignee->id,
        'sort_order' => 1,
    ]);

    $this->actingAs($subtaskAssignee->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show-employee')
            ->has('subtasks.items', 1)
            ->where('subtasks.items.0.title', 'Resize banner'));
});

test('my tasks includes work assigned through subtasks', function () {
    $assignee = employeeWith(Ability::AccessTasks);
    $subtaskAssignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($assignee)->create(['title' => 'Parent task']);
    TaskSubtask::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Child work',
        'status' => SubtaskStatus::Pending,
        'assigned_employee_id' => $subtaskAssignee->id,
        'sort_order' => 1,
    ]);

    $this->actingAs($subtaskAssignee->user)
        ->get('/tasks')
        ->assertInertia(fn ($page) => $page
            ->where('pageTitle', 'My Tasks')
            ->has('tasks.data', 1)
            ->where('tasks.data.0.title', 'Parent task'));
});

test('an assignee cannot create reminders on their task', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $recipient = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/reminders", [
            'remind_at' => now()->addHour()->toDateTimeString(),
            'recipient_user_id' => $recipient->user_id,
            'message' => 'Please wrap this up.',
        ])
        ->assertForbidden();
});

test('an assignee cannot create subtasks on their task', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/subtasks", ['title' => 'Sneak in'])
        ->assertForbidden();
});

test('an assignee cannot add checklist items', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/checklist-items", ['title' => 'Extra step'])
        ->assertForbidden();
});

test('an assignee can still complete checklist items', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $item = $task->checklistItems()->create(['title' => 'Export PNG', 'sort_order' => 1]);

    $this->actingAs($employee->user)
        ->patch("/tasks/{$task->id}/checklist-items/{$item->id}/toggle")
        ->assertRedirect();

    expect($item->fresh()->is_completed)->toBeTrue();
});

test('employee role no longer includes client or project screens', function () {
    $this->seed(\Database\Seeders\Core\RolesAndPermissionsSeeder::class);
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $employee = User::factory()->create()->syncRoles(SystemRole::Employee->value);

    expect($employee->can(Ability::ViewCompanies->value))->toBeFalse()
        ->and($employee->can(Ability::ViewProjects->value))->toBeFalse();

    $this->actingAs($employee)->get('/tasks/clients')->assertForbidden();
    $this->actingAs($employee)->get('/tasks/projects')->assertForbidden();
});
