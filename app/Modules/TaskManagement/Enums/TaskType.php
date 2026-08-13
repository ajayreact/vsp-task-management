<?php

namespace App\Modules\TaskManagement\Enums;

enum TaskType: string
{
    case Design = 'design';
    case Content = 'content';
    case Development = 'development';
    case Video = 'video';
    case Admin = 'admin';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Design => 'Design',
            self::Content => 'Content',
            self::Development => 'Development',
            self::Video => 'Video',
            self::Admin => 'Admin',
            self::Other => 'Other',
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
