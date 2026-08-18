<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Notifications\StaffDatabaseNotification;

test('the notification feed returns recent notifications as json', function () {
    $employee = employeeWith(Ability::AccessTasks);

    $employee->user->notify(new StaffDatabaseNotification([
        'event' => 'task.assigned',
        'title' => 'New Task Assigned',
        'body' => 'You have been assigned to the task: Caption draft',
        'url' => '/tasks/1',
        'task_id' => 1,
    ]));

    $this->actingAs($employee->user)
        ->getJson('/notifications/feed')
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('recent.0.title', 'New Task Assigned')
        ->assertJsonPath('recent.0.event', 'task.assigned');
});

test('guests cannot access the notification feed', function () {
    $this->getJson('/notifications/feed')->assertUnauthorized();
});
