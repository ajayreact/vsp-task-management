<?php

namespace App\Modules\TaskManagement\Enums;

enum AvailabilityStatus: string
{
    case Available = 'available';
    case Leave = 'leave';
    case HalfDay = 'half_day';
    case Holiday = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Leave => 'Leave',
            self::HalfDay => 'Half day',
            self::Holiday => 'Holiday',
        };
    }

    /**
     * Whether this day still contributes any hours toward capacity.
     */
    public function hasCapacity(): bool
    {
        return in_array($this, [self::Available, self::HalfDay], true);
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
