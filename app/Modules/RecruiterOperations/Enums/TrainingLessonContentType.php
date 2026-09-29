<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * The main material of a lesson. Every type may also carry written body text,
 * which is what "Listen to Lesson" reads aloud.
 */
enum TrainingLessonContentType: string
{
    case Text = 'text';
    case Video = 'video';
    case Pdf = 'pdf';
    case Image = 'image';
    case ExternalResource = 'external_resource';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Video => 'Video',
            self::Pdf => 'PDF',
            self::Image => 'Image',
            self::ExternalResource => 'External resource',
        };
    }

    /**
     * Types whose material is an uploaded file.
     */
    public function usesFile(): bool
    {
        return in_array($this, [self::Video, self::Pdf, self::Image], true);
    }

    /**
     * File extensions accepted for this type's upload.
     *
     * @return list<string>
     */
    public function allowedExtensions(): array
    {
        return match ($this) {
            self::Video => ['mp4', 'webm', 'mov'],
            self::Pdf => ['pdf'],
            self::Image => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
            default => [],
        };
    }

    /**
     * MIME types accepted for this type's upload, checked against file content.
     *
     * @return list<string>
     */
    public function allowedMimeTypes(): array
    {
        return match ($this) {
            self::Video => ['video/mp4', 'video/webm', 'video/quicktime'],
            self::Pdf => ['application/pdf'],
            self::Image => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
            default => [],
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
