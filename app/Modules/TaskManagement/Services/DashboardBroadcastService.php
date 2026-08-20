<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Enums\Ability;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Events\CommandCenterUpdated;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Live Command Center refresh over existing Reverb private user channels.
 */
class DashboardBroadcastService
{
    public function refresh(User $actor, ?Task $task = null): void
    {
        if (! $this->shouldBroadcast()) {
            return;
        }

        $recipients = $this->recipientsFor($actor, $task);

        if ($recipients === []) {
            return;
        }

        try {
            broadcast(new CommandCenterUpdated($recipients));
        } catch (Throwable $exception) {
            Log::warning('Command Center broadcast failed.', [
                'exception' => $exception->getMessage(),
                'recipient_count' => count($recipients),
            ]);
        }
    }

    /**
     * @return list<int>
     */
    protected function recipientsFor(User $actor, ?Task $task): array
    {
        /** @var Collection<int, int> $ids */
        $ids = collect([$actor->id]);

        if ($task !== null) {
            $task->loadMissing('assignee.user');

            if ($task->assignee?->user !== null) {
                $ids->push($task->assignee->user->id);
            }
        }

        $ids = $ids->merge(
            User::query()
                ->internal()
                ->where('is_active', true)
                ->permission(Ability::ViewAllTasks->value)
                ->pluck('id'),
        );

        return $ids->unique()->values()->all();
    }

    protected function shouldBroadcast(): bool
    {
        $driver = config('broadcasting.default');

        return filled($driver) && $driver !== 'null';
    }
}
