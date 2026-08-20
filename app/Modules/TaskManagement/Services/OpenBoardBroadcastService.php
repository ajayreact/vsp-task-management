<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Events\OpenBoardTaskClaimed;
use App\Modules\TaskManagement\Events\OpenBoardTaskPublished;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Live open-board updates over the existing Reverb private user channels.
 */
class OpenBoardBroadcastService
{
    public function __construct(protected TaskNotifier $notifier) {}

    public function taskClaimed(Task $task, User $actor): void
    {
        if (! $this->shouldBroadcast()) {
            return;
        }

        $recipients = $this->notifier
            ->eligibleOpenBoardEmployeesFor($task)
            ->reject(fn (User $user) => $user->id === $actor->id)
            ->pluck('id')
            ->all();

        if ($recipients === []) {
            return;
        }

        try {
            broadcast(new OpenBoardTaskClaimed($task->id, $recipients));
        } catch (Throwable $exception) {
            Log::warning('Open board claim broadcast failed.', [
                'task_id' => $task->id,
                'exception' => $exception->getMessage(),
                'recipient_count' => count($recipients),
            ]);
        }
    }

    public function taskPublished(Task $task, User $actor): void
    {
        if (! $this->shouldBroadcast()) {
            return;
        }

        $recipients = $this->notifier
            ->eligibleOpenBoardEmployeesFor($task)
            ->reject(fn (User $user) => $user->id === $actor->id)
            ->pluck('id')
            ->all();

        if ($recipients === []) {
            return;
        }

        try {
            broadcast(new OpenBoardTaskPublished($this->serializeBoardTask($task), $recipients));
        } catch (Throwable $exception) {
            Log::warning('Open board publish broadcast failed.', [
                'task_id' => $task->id,
                'exception' => $exception->getMessage(),
                'recipient_count' => count($recipients),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeBoardTask(Task $task): array
    {
        $task->loadMissing(['project:id,name', 'department:id,name']);

        return [
            'id' => $task->id,
            'title' => $task->title,
            'type' => $task->type->label(),
            'priority' => $task->priority->value,
            'priority_label' => $task->priority->label(),
            'project_name' => $task->project->name,
            'department_id' => $task->department_id,
            'department_name' => $task->department?->name,
            'estimated_hours' => $task->estimated_hours,
            'due_at' => $task->due_at?->toIso8601String(),
        ];
    }

    protected function shouldBroadcast(): bool
    {
        $driver = config('broadcasting.default');

        return filled($driver) && $driver !== 'null';
    }
}
