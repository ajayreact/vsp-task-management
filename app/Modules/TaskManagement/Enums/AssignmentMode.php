<?php

namespace App\Modules\TaskManagement\Enums;

/**
 * How a task is meant to reach a person. Distinct from AssignmentAction, which
 * records how a specific person actually came to hold it.
 */
enum AssignmentMode: string
{
    case Direct = 'direct';
    case Open = 'open';

    public function label(): string
    {
        return match ($this) {
            self::Direct => 'Directly assigned',
            self::Open => 'Open to claim',
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
