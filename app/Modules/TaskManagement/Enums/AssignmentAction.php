<?php

namespace App\Modules\TaskManagement\Enums;

/**
 * How this particular person came to hold the task: handed it by a manager, or
 * took it off the open board themselves.
 */
enum AssignmentAction: string
{
    case Direct = 'direct';
    case Claim = 'claim';

    public function label(): string
    {
        return match ($this) {
            self::Direct => 'Assigned',
            self::Claim => 'Claimed',
        };
    }
}
