<?php

namespace App\Modules\TaskManagement\Enums;

enum ContentCalendarPlatform: string
{
    case Instagram = 'instagram';
    case Facebook = 'facebook';
    case LinkedIn = 'linkedin';
    case YouTube = 'youtube';
    case WhatsApp = 'whatsapp';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::Facebook => 'Facebook',
            self::LinkedIn => 'LinkedIn',
            self::YouTube => 'YouTube',
            self::WhatsApp => 'WhatsApp',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $platform) => ['value' => $platform->value, 'label' => $platform->label()],
            self::cases(),
        );
    }
}
