<?php

namespace App\Modules\Attendance\Enums;

/**
 * Daily attendance state for an employee. Absent is derived — it is not stored.
 */
enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case OnBreak = 'on_break';
    case CheckedOut = 'checked_out';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Working',
            self::Late => 'Late',
            self::OnBreak => 'On break',
            self::CheckedOut => 'Checked out',
        };
    }

    public function isWorking(): bool
    {
        return match ($this) {
            self::Present, self::Late => true,
            default => false,
        };
    }
}
