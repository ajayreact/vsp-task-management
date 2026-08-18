<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Notifications\StaffDatabaseNotification;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\Event;

test('a stored staff notification is also queued for broadcast to the recipient', function () {
    configureReverbForChannelAuth();
    Event::fake([BroadcastNotificationCreated::class]);

    $manager = employeeWith(
        Ability::AccessTasks,
        Ability::ManageTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['created_by_user_id' => $manager->user_id]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id])
        ->assertRedirect();

    expect($employee->user->notifications()->count())->toBe(1);

    Event::assertDispatched(BroadcastNotificationCreated::class, function (BroadcastNotificationCreated $event) use ($employee) {
        return $event->notifiable->is($employee->user)
            && $event->notification instanceof StaffDatabaseNotification
            && ($event->data['event'] ?? null) === 'task.assigned'
            && is_string($event->notification->id)
            && $event->notification->id !== '';
    });

    Event::assertNotDispatched(BroadcastNotificationCreated::class, function (BroadcastNotificationCreated $event) use ($manager) {
        return $event->notifiable->is($manager->user);
    });
});

test('the actor does not receive a broadcast when they trigger a notification', function () {
    configureReverbForChannelAuth();
    Event::fake([BroadcastNotificationCreated::class]);

    $manager = employeeWith(
        Ability::AccessTasks,
        Ability::ManageTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['created_by_user_id' => $manager->user_id]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id]);

    expect($manager->user->notifications()->count())->toBe(0);

    Event::assertDispatched(BroadcastNotificationCreated::class, function (BroadcastNotificationCreated $event) use ($employee) {
        return $event->notifiable->is($employee->user);
    });
});

test('a user can authorize their own private notification channel', function () {
    configureReverbForChannelAuth();

    $employee = employeeWith(Ability::AccessTasks);

    $this->actingAs($employee->user)
        ->post('/broadcasting/auth', [
            'channel_name' => 'private-staff.user.'.$employee->user->id,
            'socket_id' => '1234.5678',
        ])
        ->assertSuccessful()
        ->assertJsonStructure(['auth']);
});

test('a user cannot authorize another users private notification channel', function () {
    configureReverbForChannelAuth();

    $employee = employeeWith(Ability::AccessTasks);
    $other = employeeWith(Ability::AccessTasks);

    $this->actingAs($employee->user)
        ->post('/broadcasting/auth', [
            'channel_name' => 'private-staff.user.'.$other->user->id,
            'socket_id' => '1234.5678',
        ])
        ->assertForbidden();
});

test('database and broadcast share the same notification id', function () {
    configureReverbForChannelAuth();
    Event::fake([BroadcastNotificationCreated::class]);

    $manager = employeeWith(
        Ability::AccessTasks,
        Ability::ManageTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['created_by_user_id' => $manager->user_id]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/assign", ['employee_id' => $employee->id]);

    $stored = $employee->user->notifications()->sole();

    Event::assertDispatched(BroadcastNotificationCreated::class, function (BroadcastNotificationCreated $event) use ($stored) {
        return $event->notification->id === $stored->id;
    });
});
