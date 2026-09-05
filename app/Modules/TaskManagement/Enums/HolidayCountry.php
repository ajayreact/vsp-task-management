<?php

namespace App\Modules\TaskManagement\Enums;

enum HolidayCountry: string
{
    case India = 'india';
    case Usa = 'usa';

    public function label(): string
    {
        return match ($this) {
            self::India => 'India',
            self::Usa => 'United States',
        };
    }

    public function flagEmoji(): string
    {
        return match ($this) {
            self::India => '🇮🇳',
            self::Usa => '🇺🇸',
        };
    }
}
