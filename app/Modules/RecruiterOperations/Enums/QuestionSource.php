<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * How a question entered the system. A copy placed in a quiz version keeps
 * the source of the bank question it came from.
 */
enum QuestionSource: string
{
    case Manual = 'manual';
    case Excel = 'excel';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Excel => 'Excel import',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $source) => ['value' => $source->value, 'label' => $source->label()], self::cases());
    }
}
