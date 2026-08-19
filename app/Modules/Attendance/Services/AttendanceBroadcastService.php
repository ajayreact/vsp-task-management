<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Events\AttendanceDashboardUpdated;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        try {
            broadcast(new AttendanceDashboardUpdated($recipients));
        } catch (Throwable $exception) {
            // Attendance is already persisted; a live dashboard refresh must not
            // turn a successful check-in/out into a 500 for the employee.
            Log::warning('Attendance dashboard broadcast failed.', [
                'exception' => $exception->getMessage(),
                'recipient_count' => count($recipients),
            ]);
        }
    }

    protected function shouldBroadcast(): bool
    {
        $driver = config('broadcasting.default');

        return filled($driver) && $driver !== 'null';
    }
}
