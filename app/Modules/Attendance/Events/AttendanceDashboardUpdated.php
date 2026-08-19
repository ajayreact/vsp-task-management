<?php

namespace App\Modules\Attendance\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Tells Super Admins to reload the attendance dashboard snapshot.
 */
class AttendanceDashboardUpdated implements ShouldBroadcastNow
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
        return 'attendance-dashboard.updated';
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
