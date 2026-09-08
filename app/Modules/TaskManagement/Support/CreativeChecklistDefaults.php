<?php

namespace App\Modules\TaskManagement\Support;

use App\Modules\TaskManagement\Enums\ContentCalendarType;

/**
 * Default quality-control checklist templates for creative tasks.
 *
 * Seeded once on create (and missing items on creative_type change) — never on page open.
 */
class CreativeChecklistDefaults
{
    public const GROUP_QUALITY = 'quality';

    public const GROUP_VIDEO = 'video';

    public const SOURCE_SYSTEM = 'system';

    public const SOURCE_CUSTOM = 'custom';

    /**
     * @return list<array{key: string, title: string, group: string}>
     */
    public static function common(): array
    {
        return [
            [
                'key' => 'quality.content_spellings',
                'title' => 'Check the Content Spellings',
                'group' => self::GROUP_QUALITY,
            ],
            [
                'key' => 'quality.logo_before_uploading',
                'title' => 'Check the Logo Before Uploading',
                'group' => self::GROUP_QUALITY,
            ],
            [
                'key' => 'quality.contact_details',
                'title' => 'Check the Contact Details',
                'group' => self::GROUP_QUALITY,
            ],
        ];
    }

    /**
     * @return list<array{key: string, title: string, group: string}>
     */
    public static function video(): array
    {
        return [
            [
                'key' => 'video.contact_at_end',
                'title' => 'Add Contact Details at the End of the Video',
                'group' => self::GROUP_VIDEO,
            ],
            [
                'key' => 'video.logo_on_voiceover',
                'title' => 'Whenever the Company Name Is Mentioned in the Voiceover, Display the Company Logo',
                'group' => self::GROUP_VIDEO,
            ],
            [
                'key' => 'video.watch_twice',
                'title' => 'Watch the Complete Video Twice and Check Everything Before Uploading',
                'group' => self::GROUP_VIDEO,
            ],
        ];
    }

    /**
     * Templates required for the given creative type. Empty for non-creative formats.
     *
     * @return list<array{key: string, title: string, group: string}>
     */
    public static function for(?ContentCalendarType $creativeType): array
    {
        return match ($creativeType) {
            ContentCalendarType::Poster,
            ContentCalendarType::Reel => self::common(),
            ContentCalendarType::Video => array_merge(self::common(), self::video()),
            default => [],
        };
    }

    /**
     * @return list<string>
     */
    public static function requiredKeysFor(?ContentCalendarType $creativeType): array
    {
        return array_column(self::for($creativeType), 'key');
    }
}
