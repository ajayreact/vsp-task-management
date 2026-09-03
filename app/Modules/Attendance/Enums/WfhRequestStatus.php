<?php

namespace App\Modules\Attendance\Enums;

enum WfhRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Assigned = 'assigned';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::Assigned => 'Assigned',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isActiveAuthorization(): bool
    {
        return in_array($this, [self::Approved, self::Assigned], true);
    }

    public function blocksOverlap(): bool
    {
        return in_array($this, [self::Pending, self::Approved, self::Assigned], true);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
