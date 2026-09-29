<?php

namespace App\Modules\RecruiterOperations\Enums;

enum RecruiterTaskEventType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Assigned = 'assigned';
    case Reassigned = 'reassigned';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case PutOnHold = 'put_on_hold';
    case Resumed = 'resumed';
    case Completed = 'completed';
    case Reopened = 'reopened';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Created',
            self::Updated => 'Details updated',
            self::Assigned => 'Assigned',
            self::Reassigned => 'Reassigned',
            self::Accepted => 'Accepted',
            self::Declined => 'Declined',
            self::PutOnHold => 'Put on hold',
            self::Resumed => 'Resumed',
            self::Completed => 'Completed',
            self::Reopened => 'Reopened',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Events that only set a task up. Anything else means the workflow has
     * been used and the task's history must be kept.
     *
     * @return list<self>
     */
    public static function setupEvents(): array
    {
        return [self::Created, self::Updated, self::Assigned];
    }
}
