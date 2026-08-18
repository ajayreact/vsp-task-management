<?php

use App\Modules\Core\Models\AppSetting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AppSetting::query()->firstOrCreate(
            [
                'group' => 'task_management',
                'key' => 'proof_retention',
            ],
            [
                'value' => [
                    'enabled' => false,
                    'days' => null,
                ],
            ],
        );
    }

    public function down(): void
    {
        AppSetting::query()
            ->where('group', 'task_management')
            ->where('key', 'proof_retention')
            ->delete();
    }
};
