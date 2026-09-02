<?php

namespace App\Modules\TaskManagement\Enums;

enum CompanyLogoVariant: string
{
    case Original = 'original';
    case Transparent = 'transparent';
    case WhiteBackground = 'white_background';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Original => 'Original',
            self::Transparent => 'Transparent',
            self::WhiteBackground => 'White background',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $variant) => ['value' => $variant->value, 'label' => $variant->label()],
            self::cases(),
        );
    }
}
