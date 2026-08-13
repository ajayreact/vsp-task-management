<?php

namespace App\Modules\TaskManagement\Enums;

enum AssignmentStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Reassigned = 'reassigned';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Awaiting response',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::Reassigned => 'Reassigned elsewhere',
        };
    }

    /**
     * Whether this row still represents the person on the hook for the task.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Accepted], strict: true);
    }
}
