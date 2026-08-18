<?php

namespace App\Modules\TaskManagement\Enums;

enum TimeSource: string
{
    case Timer = 'timer';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Timer => 'Timer',
            self::Manual => 'Manual',
        };
    }
}
