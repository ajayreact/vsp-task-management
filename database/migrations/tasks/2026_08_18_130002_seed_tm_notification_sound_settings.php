<?php

use App\Modules\Core\Models\AppSetting;
use App\Modules\TaskManagement\Enums\NotificationSystemSound;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AppSetting::query()->firstOrCreate(
            [
                'group' => 'task_management',
                'key' => 'notification_sound',
            ],
            [
                'value' => [
                    'enabled' => true,
                    'source' => 'system',
                    'system_sound' => NotificationSystemSound::ClassicChime->value,
                    'custom_media_id' => null,
                ],
            ],
        );
    }

    public function down(): void
    {
        AppSetting::query()
            ->where('group', 'task_management')
            ->where('key', 'notification_sound')
            ->delete();
    }
};
