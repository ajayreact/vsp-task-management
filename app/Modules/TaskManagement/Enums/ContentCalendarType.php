<?php

namespace App\Modules\TaskManagement\Enums;

enum ContentCalendarType: string
{
    case Poster = 'poster';
    case Reel = 'reel';
    case Video = 'video';
    case Carousel = 'carousel';
    case Story = 'story';
    case Article = 'article';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Poster => 'Poster',
            self::Reel => 'Reel',
            self::Video => 'Video',
            self::Carousel => 'Carousel',
            self::Story => 'Story',
            self::Article => 'Article',
            self::Other => 'Other',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type) => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
