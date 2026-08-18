<?php

namespace App\Support;

use App\Modules\Core\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;

/**
 * Shapes database notifications for Inertia. Lives outside modules so the
 * shared middleware can present unread state without importing Task Management.
 */
class NotificationPresenter
{
    /**
     * @return array{unread_count: int, recent: list<array<string, mixed>>}
     */
    public static function forUser(?User $user): array
    {
        if ($user === null) {
            return [
                'unread_count' => 0,
                'recent' => [],
            ];
        }

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'recent' => self::map(
                $user->notifications()->latest()->limit(10)->get()
            )->all(),
        ];
    }

    /**
     * @param  Collection<int, DatabaseNotification>  $notifications
     * @return Collection<int, array<string, mixed>>
     */
    public static function map(Collection $notifications): Collection
    {
        return $notifications->map(fn (DatabaseNotification $notification) => self::one($notification));
    }

    /**
     * @return array<string, mixed>
     */
    public static function one(DatabaseNotification $notification): array
    {
        $data = $notification->data;

        return [
            'id' => $notification->id,
            'event' => $data['event'] ?? null,
            'title' => $data['title'] ?? 'Notification',
            'body' => $data['body'] ?? '',
            'url' => $data['url'] ?? null,
            'task_id' => $data['task_id'] ?? null,
            'timesheet_id' => $data['timesheet_id'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
