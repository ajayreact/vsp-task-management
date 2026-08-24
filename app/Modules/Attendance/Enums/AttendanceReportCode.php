<?php

namespace App\Modules\Attendance\Enums;

/**
 * Display codes for attendance reports. Distinct from stored AttendanceStatus.
 */
enum AttendanceReportCode: string
{
    case Present = 'P';
    case Absent = 'A';
    case Late = 'L';
    case WeekOff = 'OFF';
    case Future = '';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'Present',
            self::Absent => 'Absent',
            self::Late => 'Late',
            self::WeekOff => 'Week off',
            self::Future => '',
        };
    }

    public function countsTowardPresent(): bool
    {
        return in_array($this, [self::Present, self::Late], true);
    }

    public function countsTowardAbsent(): bool
    {
        return $this === self::Absent;
    }

    public function countsTowardLate(): bool
    {
        return $this === self::Late;
    }

    public function countsTowardWeekOff(): bool
    {
        return $this === self::WeekOff;
    }
}
