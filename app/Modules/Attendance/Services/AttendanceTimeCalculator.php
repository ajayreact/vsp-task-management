<?php

namespace App\Modules\Attendance\Services;

use App\Modules\Attendance\Models\AttendanceBreak;
use App\Modules\Attendance\Models\AttendanceEntry;
use Illuminate\Support\Carbon;

class AttendanceTimeCalculator
{
    public function activeBreak(AttendanceEntry $entry): ?AttendanceBreak
    {
        return $entry->breaks
            ->first(fn (AttendanceBreak $break) => $break->ended_at === null);
    }

    public function grossWorkingSeconds(AttendanceEntry $entry, ?Carbon $until = null): int
    {
        if ($entry->check_in_at === null) {
            return 0;
        }

        $until = $until ?? now();

        if ($entry->check_out_at !== null) {
            $until = $entry->check_out_at;
        }

        return (int) $entry->check_in_at->diffInSeconds($until);
    }

    public function activeBreakSeconds(AttendanceEntry $entry, ?Carbon $until = null): int
    {
        $activeBreak = $this->activeBreak($entry);

        if ($activeBreak === null) {
            return 0;
        }

        $until = $until ?? now();

        return (int) $activeBreak->started_at->diffInSeconds($until);
    }

    public function netWorkingSeconds(AttendanceEntry $entry, ?Carbon $until = null): int
    {
        if ($entry->net_working_seconds !== null && $entry->check_out_at !== null) {
            return $entry->net_working_seconds;
        }

        $gross = $this->grossWorkingSeconds($entry, $until);
        $breakSeconds = $entry->total_break_seconds + $this->activeBreakSeconds($entry, $until);

        return max(0, $gross - $breakSeconds);
    }
}
