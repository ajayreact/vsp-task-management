<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * Lifecycle of an assessment and of each of its versions. Only a draft
 * version can change; published and archived versions are frozen.
 */
enum AssessmentStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    /**
     * Allowed moves for a version: draft -> published -> archived.
     */
    public function canBecome(self $next): bool
    {
        return match ($this) {
            self::Draft => $next === self::Published,
            self::Published => $next === self::Archived,
            self::Archived => false,
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
