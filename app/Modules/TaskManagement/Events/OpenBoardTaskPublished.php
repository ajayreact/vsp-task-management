<?php

namespace App\Modules\TaskManagement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies eligible employees that a new task is available on the open board.
 */
class OpenBoardTaskPublished implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  array<string, mixed>  $task
     * @param  list<int>  $recipientUserIds
     */
    public function __construct(
        public array $task,
        public array $recipientUserIds,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return array_map(
            fn (int $userId) => new PrivateChannel('staff.user.'.$userId),
            $this->recipientUserIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'open-board.task-published';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task' => $this->task,
        ];
    }
}
