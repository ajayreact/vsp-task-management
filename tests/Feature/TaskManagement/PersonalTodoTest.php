<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\PersonalTodoStatus;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\PersonalTodo;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskChecklistItem;
use App\Modules\TaskManagement\Models\TaskSubtask;
use App\Modules\TaskManagement\Notifications\StaffDatabaseNotification;
use App\Modules\TaskManagement\Services\TaskReminderService;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('employee can create a personal todo', function () {
    $employee = employeeWith(Ability::AccessTasks);

    $this->actingAs($employee->user)
        ->post('/tasks/personal-todos', [
            'title' => 'Call client',
            'due_date' => today()->toDateString(),
            'due_time' => '11:00',
            'priority' => 'high',
            'note' => 'Follow up on approval',
            'reminder_minutes_before' => 30,
        ])
        ->assertRedirect();

    $todo = PersonalTodo::query()->sole();

    expect($todo->user_id)->toBe($employee->user_id)
        ->and($todo->title)->toBe('Call client')
        ->and($todo->priority)->toBe(TaskPriority::High)
        ->and($todo->status)->toBe(PersonalTodoStatus::Pending)
        ->and($todo->reminder_at)->not->toBeNull();
});

test('employee can view their own personal todo on the todos page', function () {
    $employee = employeeWith(Ability::AccessTasks);
    PersonalTodo::factory()->create([
        'user_id' => $employee->user_id,
        'title' => 'Send report',
        'due_date' => today(),
    ]);

    $this->actingAs($employee->user)
        ->get('/tasks/todos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('TaskManagement/todos/index')
            ->where('items.0.title', 'Send report'));
});

test('employee cannot view another employees personal todo', function () {
    $owner = employeeWith(Ability::AccessTasks);
    $other = employeeWith(Ability::AccessTasks);
    $todo = PersonalTodo::factory()->create([
        'user_id' => $owner->user_id,
        'title' => 'Private reminder',
    ]);

    $this->actingAs($other->user)
        ->put("/tasks/personal-todos/{$todo->id}", [
            'title' => 'Hacked',
            'priority' => 'normal',
        ])
        ->assertForbidden();
});

test('employee can edit their own personal todo', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $todo = PersonalTodo::factory()->create([
        'user_id' => $employee->user_id,
        'title' => 'Old title',
    ]);

    $this->actingAs($employee->user)
        ->put("/tasks/personal-todos/{$todo->id}", [
            'title' => 'Updated title',
            'priority' => 'urgent',
            'due_date' => today()->addDay()->toDateString(),
        ])
        ->assertRedirect();

    expect($todo->fresh()->title)->toBe('Updated title')
        ->and($todo->fresh()->priority)->toBe(TaskPriority::Urgent);
});

test('employee can complete a personal todo and completed_at is recorded', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $todo = PersonalTodo::factory()->create([
        'user_id' => $employee->user_id,
    ]);

    $this->actingAs($employee->user)
        ->patch("/tasks/personal-todos/{$todo->id}/complete")
        ->assertRedirect();

    $todo->refresh();

    expect($todo->status)->toBe(PersonalTodoStatus::Completed)
        ->and($todo->completed_at)->not->toBeNull();
});

test('employee can delete their own personal todo', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $todo = PersonalTodo::factory()->create([
        'user_id' => $employee->user_id,
    ]);

    $this->actingAs($employee->user)
        ->delete("/tasks/personal-todos/{$todo->id}")
        ->assertRedirect();

    expect(PersonalTodo::query()->count())->toBe(0);
});

test('priority and due date filtering work on the todos page', function () {
    $employee = employeeWith(Ability::AccessTasks);

    PersonalTodo::factory()->create([
        'user_id' => $employee->user_id,
        'title' => 'High today',
        'priority' => TaskPriority::High,
        'due_date' => today(),
    ]);

    PersonalTodo::factory()->create([
        'user_id' => $employee->user_id,
        'title' => 'Low tomorrow',
        'priority' => TaskPriority::Low,
        'due_date' => today()->addDay(),
    ]);

    $this->actingAs($employee->user)
        ->get('/tasks/todos?tab=today&priority=high')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('items', fn ($items) => collect($items)->pluck('title')->all() === ['High today']));
});

test('overdue personal todos are grouped as overdue', function () {
    $employee = employeeWith(Ability::AccessTasks);

    PersonalTodo::factory()->create([
        'user_id' => $employee->user_id,
        'title' => 'Missed call',
        'due_date' => today()->subDay(),
    ]);

    $this->actingAs($employee->user)
        ->get('/tasks/todos?tab=overdue')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('sections.overdue.count', 1)
            ->where('items.0.title', 'Missed call')
            ->where('items.0.is_overdue', true));
});

test('upcoming personal todos appear in the upcoming tab', function () {
    $employee = employeeWith(Ability::AccessTasks);

    PersonalTodo::factory()->create([
        'user_id' => $employee->user_id,
        'title' => 'Tomorrow follow up',
        'due_date' => today()->addDay(),
    ]);

    $this->actingAs($employee->user)
        ->get('/tasks/todos?tab=upcoming')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.title', 'Tomorrow follow up'));
});

test('completed personal todos appear in the completed tab for today', function () {
    $employee = employeeWith(Ability::AccessTasks);

    PersonalTodo::factory()->completed()->create([
        'user_id' => $employee->user_id,
        'title' => 'Done item',
        'due_date' => today(),
        'completed_at' => now(),
    ]);

    $this->actingAs($employee->user)
        ->get('/tasks/todos?tab=completed')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.title', 'Done item')
            ->where('items.0.is_completed', true));
});

test('assigned tasks appear in dashboard my todo snapshot without duplicating records', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'due_at' => today()->setTime(14, 0),
        'priority' => TaskPriority::High,
    ]);

    $before = PersonalTodo::query()->count();

    $this->actingAs($employee->user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('snapshot.my_todo.today.items.0.title', $task->title)
            ->where('snapshot.my_todo.today.items.0.source', 'task')
            ->where('snapshot.my_todo.today.items.0.href', "/tasks/{$task->id}"));

    expect(PersonalTodo::query()->count())->toBe($before);
});

test('completed tasks do not appear in active todo sections', function () {
    $employee = employeeWith(Ability::AccessTasks);

    Task::factory()->acceptedBy($employee)->create([
        'status' => TaskStatus::Completed,
        'completed_at' => now()->subDay(),
        'due_at' => today()->subDay(),
    ]);

    $this->actingAs($employee->user)
        ->get('/tasks/todos?tab=all')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('items', []));
});

test('task checklist progress is included in my todo payload', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($employee)->create([
        'due_at' => today()->setTime(10, 0),
    ]);

    TaskChecklistItem::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'One',
        'is_completed' => true,
        'sort_order' => 1,
    ]);
    TaskChecklistItem::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Two',
        'is_completed' => false,
        'sort_order' => 2,
    ]);

    $this->actingAs($employee->user)
        ->get('/tasks/todos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.checklist.completed', 1)
            ->where('items.0.checklist.total', 2));
});

test('assigned subtasks appear nested under the parent task in my todo payload', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $other = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->acceptedBy($other)->create([
        'due_at' => today()->setTime(15, 0),
    ]);

    TaskSubtask::query()->create([
        'tm_task_id' => $task->id,
        'title' => 'Collect client logo',
        'status' => SubtaskStatus::Pending,
        'assigned_employee_id' => $employee->id,
        'sort_order' => 1,
    ]);

    $this->actingAs($employee->user)
        ->get('/tasks/todos')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('items.0.title', $task->title)
            ->where('items.0.subtasks.0.title', 'Collect client logo'));
});

test('personal todo reminders use the existing notification infrastructure', function () {
    Notification::fake();

    $employee = employeeWith(Ability::AccessTasks);
    $todo = PersonalTodo::factory()->create([
        'user_id' => $employee->user_id,
        'title' => 'Call client',
        'reminder_at' => now()->subMinute(),
        'reminder_sent_at' => null,
    ]);

    app(TaskReminderService::class)->sendDueReminders();

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) {
        return $notification->payload['event'] === 'todo.reminder'
            && str_contains($notification->payload['body'], 'Call client');
    });

    expect($todo->fresh()->reminder_sent_at)->not->toBeNull();
});

test('employee can move an overdue personal todo to today', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $todo = PersonalTodo::factory()->create([
        'user_id' => $employee->user_id,
        'due_date' => today()->subDays(2),
    ]);

    $this->actingAs($employee->user)
        ->patch("/tasks/personal-todos/{$todo->id}/move-to-today")
        ->assertRedirect();

    expect($todo->fresh()->due_date?->toDateString())->toBe(today()->toDateString());
});

test('unauthenticated users cannot access personal todos', function () {
    $this->get('/tasks/todos')->assertRedirect('/login');
});
