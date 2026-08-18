<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Events\CommandCenterUpdated;
use App\Modules\TaskManagement\Events\OpenBoardTaskClaimed;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Support\Facades\Event;

test('claiming a task broadcasts a command center refresh', function () {
    configureReverbForChannelAuth();
    Event::fake([CommandCenterUpdated::class, OpenBoardTaskClaimed::class]);

    $claimer = employeeWith(Ability::AccessTasks);
    $viewer = employeeWith(Ability::AccessTasks, Ability::ViewAllTasks);
    $task = Task::factory()->open()->create();

    $this->actingAs($claimer->user)
        ->post("/tasks/{$task->id}/claim")
        ->assertRedirect(route('tasks.show', $task));

    Event::assertDispatched(CommandCenterUpdated::class, function (CommandCenterUpdated $event) use ($viewer, $claimer) {
        return in_array($viewer->user->id, $event->recipientUserIds, true)
            && in_array($claimer->user->id, $event->recipientUserIds, true);
    });
});
