<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskComment;
use App\Modules\TaskManagement\Notifications\StaffDatabaseNotification;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;

test('an authorized assignee can create a comment on their task', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/comments", ['body' => 'Need the brand palette before I start.'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $comment = TaskComment::query()->sole();

    expect($comment->tm_task_id)->toBe($task->id)
        ->and($comment->user_id)->toBe($employee->user->id)
        ->and($comment->body)->toBe('Need the brand palette before I start.');
});

test('posting a comment reloads the task page with the new discussion entry', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/comments", ['body' => 'Draft is ready for review.'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $comment = TaskComment::query()->sole();

    $this->actingAs($employee->user)
        ->get("/tasks/{$task->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('TaskManagement/tasks/show-employee')
            ->has('comments', 1)
            ->where('comments.0.id', $comment->id)
            ->where('comments.0.body', 'Draft is ready for review.')
            ->where('comments.0.author_name', $employee->user->name));
});

test('posting a comment succeeds when reverb broadcast notifications are unavailable', function () {
    configureReverbForChannelAuth();

    $assignee = employeeWith(Ability::AccessTasks);
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $assignee->id,
    ]);

    Broadcast::shouldReceive('connection')->andThrow(new RuntimeException('Reverb unavailable'));

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/comments", ['body' => 'Please share a draft by EOD.'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $comment = TaskComment::query()->sole();

    expect($comment->body)->toBe('Please share a draft by EOD.');

    $notification = $assignee->user->notifications()->sole();

    expect($notification->data['event'] ?? null)->toBe('task.comment')
        ->and($notification->data['task_id'] ?? null)->toBe($task->id);
});

test('an unauthorized user cannot comment on someone elses task', function () {
    $owner = employeeWith(Ability::AccessTasks);
    $bystander = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $owner->id,
    ]);

    $this->actingAs($bystander->user)
        ->post("/tasks/{$task->id}/comments", ['body' => 'Hello'])
        ->assertForbidden();

    expect(TaskComment::query()->count())->toBe(0);
});

test('the comment author can edit their own comment', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $comment = TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $employee->user->id,
        'body' => 'Original note',
    ]);

    $this->actingAs($employee->user)
        ->put("/tasks/{$task->id}/comments/{$comment->id}", ['body' => 'Updated note'])
        ->assertRedirect();

    expect($comment->fresh()->body)->toBe('Updated note');
});

test('the comment author can delete their own comment', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $comment = TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $employee->user->id,
        'body' => 'Temporary note',
    ]);

    $this->actingAs($employee->user)
        ->delete("/tasks/{$task->id}/comments/{$comment->id}")
        ->assertRedirect();

    expect(TaskComment::query()->count())->toBe(0);
});

test('a normal employee cannot edit another users comment', function () {
    $owner = employeeWith(Ability::AccessTasks);
    $other = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $owner->id,
    ]);

    $comment = TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $owner->user->id,
        'body' => 'Assignee note',
    ]);

    $this->actingAs($other->user)
        ->put("/tasks/{$task->id}/comments/{$comment->id}", ['body' => 'Hacked'])
        ->assertForbidden();

    expect($comment->fresh()->body)->toBe('Assignee note');
});

test('a normal employee cannot delete another users comment', function () {
    $owner = employeeWith(Ability::AccessTasks);
    $other = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $owner->id,
    ]);

    $comment = TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $owner->user->id,
        'body' => 'Assignee note',
    ]);

    $this->actingAs($other->user)
        ->delete("/tasks/{$task->id}/comments/{$comment->id}")
        ->assertForbidden();
});

test('a team lead can delete any comment on a visible task', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $lead = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $comment = TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $employee->user->id,
        'body' => 'Needs moderation',
    ]);

    $this->actingAs($lead->user)
        ->delete("/tasks/{$task->id}/comments/{$comment->id}")
        ->assertRedirect();

    expect(TaskComment::query()->count())->toBe(0);
});

test('a super admin can delete any comment on a visible task', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $comment = TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $employee->user->id,
        'body' => 'Needs moderation',
    ]);

    $this->actingAs(superAdmin())
        ->delete("/tasks/{$task->id}/comments/{$comment->id}")
        ->assertRedirect();

    expect(TaskComment::query()->count())->toBe(0);
});

test('comments cannot be accessed through another tasks route', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);
    $otherTask = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $comment = TaskComment::query()->create([
        'tm_task_id' => $task->id,
        'user_id' => $employee->user->id,
        'body' => 'On task one',
    ]);

    $this->actingAs($employee->user)
        ->delete("/tasks/{$otherTask->id}/comments/{$comment->id}")
        ->assertNotFound();
});

test('commenting notifies the assignee but not the commenter', function () {
    Notification::fake();

    $assignee = employeeWith(Ability::AccessTasks);
    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $assignee->id,
    ]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/comments", ['body' => 'Please share a draft by EOD.'])
        ->assertRedirect();

    Notification::assertSentTo($assignee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) use ($task) {
        return $notification->payload['event'] === 'task.comment'
            && $notification->payload['task_id'] === $task->id;
    });

    Notification::assertNotSentTo($manager->user, StaffDatabaseNotification::class);
});
