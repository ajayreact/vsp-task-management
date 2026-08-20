<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Services\UserNotificationPreferenceService;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskComment;
use App\Modules\TaskManagement\Notifications\StaffDatabaseNotification;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Notification;

test('an employee comment notifies oversight users but not the commenter', function () {
    Notification::fake();

    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks, Ability::ManageTasks);
    $assignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $assignee->id,
        'created_by_user_id' => $manager->user_id,
    ]);

    $this->actingAs($assignee->user)
        ->post("/tasks/{$task->id}/comments", ['body' => 'Need clarification on the brief.'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($manager->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) use ($task, $assignee) {
        return $notification->payload['event'] === 'task.comment'
            && $notification->payload['task_id'] === $task->id
            && ($notification->payload['actor']['id'] ?? null) === $assignee->user->id;
    });

    Notification::assertNotSentTo($assignee->user, StaffDatabaseNotification::class);
});

test('an admin comment notifies the assignee but not the admin', function () {
    Notification::fake();

    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks, Ability::ManageTasks);
    $assignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $assignee->id,
        'created_by_user_id' => $manager->user_id,
    ]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/comments", ['body' => 'Please share a draft by EOD.'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($assignee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) use ($task, $manager) {
        return $notification->payload['event'] === 'task.comment'
            && $notification->payload['task_id'] === $task->id
            && ($notification->payload['actor']['id'] ?? null) === $manager->user->id;
    });

    Notification::assertNotSentTo($manager->user, StaffDatabaseNotification::class);
});

test('comment notifications persist when broadcast fails after database delivery', function () {
    configureReverbForChannelAuth();

    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks, Ability::ManageTasks);
    $assignee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create([
        'status' => TaskStatus::InProgress,
        'assigned_employee_id' => $assignee->id,
        'created_by_user_id' => $manager->user_id,
    ]);

    Broadcast::shouldReceive('connection')->andThrow(new RuntimeException('Reverb unavailable'));

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/comments", ['body' => 'Updated timeline attached.'])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(TaskComment::query()->count())->toBe(1);

    $notification = $assignee->user->notifications()->sole();

    expect($notification->data['event'] ?? null)->toBe('task.comment')
        ->and($notification->data['actor']['id'] ?? null)->toBe($manager->user->id);
});

test('assignment notifications include actor metadata without users avatar column', function () {
    Notification::fake();

    $manager = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks, Ability::AssignTasks, Ability::ManageTasks);
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['created_by_user_id' => $manager->user_id]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id])
        ->assertRedirect();

    Notification::assertSentTo($employee->user, StaffDatabaseNotification::class, function (StaffDatabaseNotification $notification) use ($manager) {
        return $notification->payload['event'] === 'task.assigned'
            && ($notification->payload['actor']['name'] ?? null) === $manager->user->name
            && array_key_exists('avatar', $notification->payload['actor'] ?? []);
    });
});

test('users can update their notification preferences', function () {
    $employee = employeeWith(Ability::AccessTasks);
    $service = app(UserNotificationPreferenceService::class);

    $this->actingAs($employee->user)
        ->patch('/settings/notification-preferences', [
            'browser_notifications' => false,
            'notification_sound' => false,
            'in_app_notifications' => true,
        ])
        ->assertRedirect();

    $preferences = $service->forUser($employee->user);

    expect($preferences['browser_notifications'])->toBeFalse()
        ->and($preferences['notification_sound'])->toBeFalse()
        ->and($preferences['in_app_notifications'])->toBeTrue();
});
