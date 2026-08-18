<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskReminder;
use App\Modules\TaskManagement\Notifications\StaffDatabaseNotification;
use App\Modules\TaskManagement\Services\TaskReminderService;
use Illuminate\Support\Facades\Notification;

test('an authorized assignee can create a reminder on their task', function () {
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
        ->assertRedirect();

    $reminder = TaskReminder::query()->sole();

    expect($reminder->tm_task_id)->toBe($task->id)
        ->and($reminder->recipient_user_id)->toBe($recipient->user_id)
        ->and($reminder->message)->toBe('Please wrap this up.')
        ->and($reminder->sent_at)->toBeNull();
});

test('an unauthorized user cannot create reminders on someone elses task', function () {
    $owner = employeeWith(Ability::AccessTasks);
    $bystander = employeeWith(Ability::AccessTasks);
    $recipient = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $owner->id,
    ]);

    $this->actingAs($bystander->user)
        ->post("/tasks/{$task->id}/reminders", [
            'remind_at' => now()->addHour()->toDateTimeString(),
            'recipient_user_id' => $recipient->user_id,
        ])
        ->assertForbidden();

    expect(TaskReminder::query()->count())->toBe(0);
});

test('a pending reminder can be cancelled before it is sent', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $reminder = TaskReminder::query()->create([
        'tm_task_id' => $task->id,
        'recipient_user_id' => $employee->user_id,
        'created_by_user_id' => $employee->user_id,
        'remind_at' => now()->addHour(),
    ]);

    $this->actingAs($employee->user)
        ->delete("/tasks/{$task->id}/reminders/{$reminder->id}")
        ->assertRedirect();

    expect(TaskReminder::query()->count())->toBe(0);
});

test('due reminders are sent once through the in app notification system', function () {
    Notification::fake();

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    $reminder = TaskReminder::query()->create([
        'tm_task_id' => $task->id,
        'recipient_user_id' => $employee->user_id,
        'created_by_user_id' => $employee->user_id,
        'remind_at' => now()->subMinute(),
        'message' => 'Deadline is close.',
    ]);

    expect(app(TaskReminderService::class)->sendDueReminders())->toBe(1);

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) use ($task) {
        return $notification->payload['event'] === 'task.reminder'
            && $notification->payload['task_id'] === $task->id
            && str_contains($notification->payload['body'], 'Deadline is close.');
    });

    expect($reminder->fresh()->sent_at)->not->toBeNull();
});

test('running the reminder command twice does not duplicate notifications', function () {
    Notification::fake();

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $employee->id,
    ]);

    TaskReminder::query()->create([
        'tm_task_id' => $task->id,
        'recipient_user_id' => $employee->user_id,
        'created_by_user_id' => $employee->user_id,
        'remind_at' => now()->subMinute(),
    ]);

    $service = app(TaskReminderService::class);

    expect($service->sendDueReminders())->toBe(1)
        ->and($service->sendDueReminders())->toBe(0);

    Notification::assertSentTimes(StaffDatabaseNotification::class, 1);
});

test('completed tasks do not send pending reminders', function () {
    Notification::fake();

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::Completed,
        'assigned_employee_id' => $employee->id,
        'completed_at' => now(),
    ]);

    $reminder = TaskReminder::query()->create([
        'tm_task_id' => $task->id,
        'recipient_user_id' => $employee->user_id,
        'created_by_user_id' => $employee->user_id,
        'remind_at' => now()->subMinute(),
    ]);

    expect(app(TaskReminderService::class)->sendDueReminders())->toBe(0);

    Notification::assertNothingSent();
    expect($reminder->fresh()->sent_at)->not->toBeNull();
});

test('deleted tasks do not send reminders because the reminder is removed with the task', function () {
    Notification::fake();

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::Draft,
        'assigned_employee_id' => $employee->id,
    ]);

    $reminder = TaskReminder::query()->create([
        'tm_task_id' => $task->id,
        'recipient_user_id' => $employee->user_id,
        'created_by_user_id' => $employee->user_id,
        'remind_at' => now()->subMinute(),
    ]);

    $task->delete();

    expect(TaskReminder::query()->whereKey($reminder->id)->exists())->toBeFalse();
    expect(app(TaskReminderService::class)->sendDueReminders())->toBe(0);

    Notification::assertNothingSent();
});

test('the reminder command is scheduled to run every minute', function () {
    $this->artisan('schedule:list')
        ->expectsOutputToContain('tasks:send-due-reminders')
        ->assertSuccessful();
});
