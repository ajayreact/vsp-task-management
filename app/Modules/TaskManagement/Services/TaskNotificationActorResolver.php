<?php

namespace App\Modules\TaskManagement\Services;

use App\Modules\Core\Models\User;

/**
 * Resolves actor metadata for task notification payloads.
 * Avatars live on Employee media — never query users.avatar.
 */
class TaskNotificationActorResolver
{
    /**
     * @return array{id: int, name: string, avatar: string|null}
     */
    public static function forUser(?User $user): ?array
    {
        if ($user === null) {
            return null;
        }

        $user->loadMissing('employee.media');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->employee?->getFirstMediaUrl('avatar', 'thumb') ?: null,
        ];
    }
}
