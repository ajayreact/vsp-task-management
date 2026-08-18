<?php

namespace App\Modules\TaskManagement\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells eligible staff to reload their Command Center snapshot.
 */
class CommandCenterUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  list<int>  $recipientUserIds
     */
    public function __construct(public array $recipientUserIds) {}

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
        return 'command-center.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'at' => now()->toIso8601String(),
        ];
    }
}
