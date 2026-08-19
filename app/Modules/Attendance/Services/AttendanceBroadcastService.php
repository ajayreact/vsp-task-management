<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Events\AttendanceDashboardUpdated;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;

/**
 * Live attendance dashboard refresh over existing Reverb private user channels.
 */
class AttendanceBroadcastService
{
    public function refresh(): void
    {
        if (! $this->shouldBroadcast()) {
            return;
        }

        $recipients = User::query()
            ->internal()
            ->where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('name', SystemRole::SuperAdmin->value))
            ->pluck('id')
            ->all();

        if ($recipients === []) {
            return;
        }

        broadcast(new AttendanceDashboardUpdated($recipients));
    }

    protected function shouldBroadcast(): bool
    {
        $driver = config('broadcasting.default');

        return filled($driver) && $driver !== 'null';
    }
}
