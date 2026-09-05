<?php

namespace App\Modules\TaskManagement\Support;

use App\Modules\TaskManagement\Enums\ContentCalendarPlatform;
use App\Modules\TaskManagement\Enums\ContentCalendarType;

final class ContentCalendarPlatformDefaults
{
    /**
     * Default platforms for a content format. Not locked — users may change them.
     *
     * @return list<ContentCalendarPlatform>
     */
    public static function for(ContentCalendarType|string $type): array
    {
        $type = $type instanceof ContentCalendarType
            ? $type
            : ContentCalendarType::tryFrom((string) $type);

        return match ($type) {
            ContentCalendarType::Poster => [
                ContentCalendarPlatform::Facebook,
                ContentCalendarPlatform::Instagram,
                ContentCalendarPlatform::LinkedIn,
            ],
            ContentCalendarType::Reel => [
                ContentCalendarPlatform::Facebook,
                ContentCalendarPlatform::Instagram,
                ContentCalendarPlatform::YouTube,
            ],
            ContentCalendarType::Video => [
                ContentCalendarPlatform::Facebook,
                ContentCalendarPlatform::YouTube,
                ContentCalendarPlatform::Instagram,
            ],
            ContentCalendarType::Carousel => [
                ContentCalendarPlatform::Facebook,
                ContentCalendarPlatform::Instagram,
                ContentCalendarPlatform::LinkedIn,
            ],
            ContentCalendarType::Story => [
                ContentCalendarPlatform::Instagram,
                ContentCalendarPlatform::Facebook,
            ],
            ContentCalendarType::Article => [
                ContentCalendarPlatform::LinkedIn,
                ContentCalendarPlatform::Facebook,
            ],
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function valuesFor(ContentCalendarType|string $type): array
    {
        return array_map(
            fn (ContentCalendarPlatform $platform) => $platform->value,
            self::for($type),
        );
    }

    /**
     * @return array<string, list<string>>
     */
    public static function map(): array
    {
        $map = [];

        foreach (ContentCalendarType::cases() as $type) {
            $map[$type->value] = self::valuesFor($type);
        }

        return $map;
    }
}
