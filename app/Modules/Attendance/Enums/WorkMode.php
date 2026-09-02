<?php

namespace App\Modules\Attendance\Enums;

enum WorkMode: string
{
    case Office = 'office';
    case Wfh = 'wfh';

    public function label(): string
    {
        return match ($this) {
            self::Office => 'Office',
            self::Wfh => 'Work From Home',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $mode) => ['value' => $mode->value, 'label' => $mode->label()],
            self::cases(),
        );
    }
}
