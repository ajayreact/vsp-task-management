<?php

namespace App\Modules\Attendance\Enums;

enum AttendanceAction: string
{
    case CheckIn = 'check_in';
    case CheckOut = 'check_out';

    public function label(): string
    {
        return match ($this) {
            self::CheckIn => 'Check in',
            self::CheckOut => 'Check out',
        };
    }
}
