<?php

namespace App\Modules\Core\Services;

use App\Modules\Core\Models\AppSetting;
use App\Modules\Core\Models\User;

/**
 * Per-user notification delivery preferences (toast, sound, browser alerts).
 * Browser permission itself remains controlled by the browser API.
 */
class UserNotificationPreferenceService
{
    private const GROUP = 'user';

    /**
     * @return array{browser_notifications: bool, notification_sound: bool, in_app_notifications: bool}
     */
    public function forUser(User $user): array
    {
        $stored = AppSetting::payload(self::GROUP, $this->key($user));

        return [
            'browser_notifications' => (bool) ($stored['browser_notifications'] ?? true),
            'notification_sound' => (bool) ($stored['notification_sound'] ?? true),
            'in_app_notifications' => (bool) ($stored['in_app_notifications'] ?? true),
        ];
    }

    /**
     * @param  array{browser_notifications?: bool, notification_sound?: bool, in_app_notifications?: bool}  $preferences
     */
    public function update(User $user, array $preferences): void
    {
        $current = $this->forUser($user);

        AppSetting::put(self::GROUP, $this->key($user), [
            'browser_notifications' => array_key_exists('browser_notifications', $preferences)
                ? (bool) $preferences['browser_notifications']
                : $current['browser_notifications'],
            'notification_sound' => array_key_exists('notification_sound', $preferences)
                ? (bool) $preferences['notification_sound']
                : $current['notification_sound'],
            'in_app_notifications' => array_key_exists('in_app_notifications', $preferences)
                ? (bool) $preferences['in_app_notifications']
                : $current['in_app_notifications'],
        ]);
    }

    protected function key(User $user): string
    {
        return 'notifications.'.$user->id;
    }
}
