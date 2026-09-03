<?php

namespace App\Modules\Attendance\Enums;

enum WfhRequestType: string
{
    case Request = 'request';
    case Assignment = 'assignment';

    public function label(): string
    {
        return match ($this) {
            self::Request => 'Request',
            self::Assignment => 'Direct Assignment',
        };
    }

    public function sourceLabel(): string
    {
        return match ($this) {
            self::Request => 'Requested by Employee',
            self::Assignment => 'Assigned by Operations',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
