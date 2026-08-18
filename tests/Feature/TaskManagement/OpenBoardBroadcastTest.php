<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Events\OpenBoardTaskClaimed;
use App\Modules\TaskManagement\Events\OpenBoardTaskPublished;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Support\Facades\Event;

test('claiming an open task broadcasts removal to other eligible employees', function () {
    configureReverbForChannelAuth();
    Event::fake([OpenBoardTaskClaimed::class]);

    $claimer = employeeWith(Ability::AccessTasks);
    $other = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->open()->create();

    $this->actingAs($claimer->user)
        ->post("/tasks/{$task->id}/claim")
        ->assertRedirect(route('tasks.show', $task));

    Event::assertDispatched(OpenBoardTaskClaimed::class, function (OpenBoardTaskClaimed $event) use ($task, $claimer, $other) {
        return $event->taskId === $task->id
            && in_array($other->user->id, $event->recipientUserIds, true)
            && ! in_array($claimer->user->id, $event->recipientUserIds, true);
    });
});

test('publishing to the open board broadcasts the task to other eligible employees', function () {
    configureReverbForChannelAuth();
    Event::fake([OpenBoardTaskPublished::class]);

    $manager = employeeWith(
        Ability::AccessTasks,
        Ability::ManageTasks,
        Ability::AssignTasks,
        Ability::ViewAllTasks,
    );
    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->create(['created_by_user_id' => $manager->user_id]);

    $this->actingAs($manager->user)
        ->post("/tasks/{$task->id}/publish")
        ->assertRedirect();

    Event::assertDispatched(OpenBoardTaskPublished::class, function (OpenBoardTaskPublished $event) use ($task, $manager, $employee) {
        return ($event->task['id'] ?? null) === $task->id
            && in_array($employee->user->id, $event->recipientUserIds, true)
            && ! in_array($manager->user->id, $event->recipientUserIds, true);
    });
});

test('open board broadcasts are skipped when broadcasting is disabled', function () {
    config(['broadcasting.default' => 'null']);
    Event::fake([OpenBoardTaskClaimed::class, OpenBoardTaskPublished::class]);

    $employee = employeeWith(Ability::AccessTasks);
    $task = Task::factory()->open()->create();

    $this->actingAs($employee->user)
        ->post("/tasks/{$task->id}/claim")
        ->assertRedirect(route('tasks.show', $task));

    Event::assertNotDispatched(OpenBoardTaskClaimed::class);
});
