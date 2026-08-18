<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Notifications\StaffDatabaseNotification;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Notifications\Events\BroadcastNotificationCreated;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;

/**
 * Channel callbacks register on the boot-time driver (null in phpunit).
 * Auth tests need Reverb with those callbacks on the active connection.
 */
function configureReverbForChannelAuth(): void
{
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app',
        'broadcasting.connections.reverb.options' => [
            'host' => '127.0.0.1',
            'port' => 8080,
            'scheme' => 'http',
            'useTLS' => false,
        ],
    ]);

    app()->forgetInstance(BroadcastManager::class);
    app()->forgetInstance(Factory::class);
    Broadcast::swap(new BroadcastManager(app()));

    require base_path('routes/channels.php');
}

test('a stored staff notification is also queued for broadcast to the recipient', function () {
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
