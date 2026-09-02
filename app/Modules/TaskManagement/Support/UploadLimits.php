<?php

namespace App\Modules\TaskManagement\Support;

use Illuminate\Http\UploadedFile;

class UploadLimits
{
    public const IMAGE_MAX_KILOBYTES = 20 * 1024;

    public const VIDEO_MAX_KILOBYTES = 100 * 1024;

    public const DOCUMENT_MAX_KILOBYTES = 50 * 1024;

    public const TASK_ATTACHMENT_MAX_KILOBYTES = 50 * 1024;

    public const TASK_ATTACHMENT_MAX_FILES = 10;

    public const NOTIFICATION_SOUND_MAX_KILOBYTES = 5120;

    /** Documented production POST body limit used for multi-file validation. */
    public const DOCUMENTED_POST_MAX_BYTES = 150 * 1024 * 1024;

    public const SPATIE_MAX_BYTES = 100 * 1024 * 1024;

    /**
     * @return list<string>
     */
    public static function imageExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    }

    /**
     * @return list<string>
     */
    public static function videoExtensions(): array
    {
        return ['mp4', 'mov', 'webm'];
    }

    /**
     * @return list<string>
     */
    public static function documentExtensions(): array
    {
        return ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'ai', 'psd'];
    }

    /**
     * @return list<string>
     */
    public static function proofExtensions(): array
    {
        return array_values(array_unique(array_merge(
            self::imageExtensions(),
            self::videoExtensions(),
            self::documentExtensions(),
        )));
    }

    public static function maxKilobytesForProofExtension(string $extension): ?int
    {
        $extension = strtolower(ltrim($extension, '.'));

        if (in_array($extension, self::imageExtensions(), true)) {
            return self::IMAGE_MAX_KILOBYTES;
        }

        if (in_array($extension, self::videoExtensions(), true)) {
            return self::VIDEO_MAX_KILOBYTES;
        }

        if (in_array($extension, self::documentExtensions(), true)) {
            return self::DOCUMENT_MAX_KILOBYTES;
        }

        return null;
    }

    public static function proofCategoryLabel(string $extension): string
    {
        $extension = strtolower(ltrim($extension, '.'));

        if (in_array($extension, self::imageExtensions(), true)) {
            return 'image';
        }

        if (in_array($extension, self::videoExtensions(), true)) {
            return 'video';
        }

        if (in_array($extension, self::documentExtensions(), true)) {
            return 'document';
        }

        return 'file';
    }

    public static function sizeExceededMessage(string $filename, int $maxKilobytes): string
    {
        return sprintf(
            '%s exceeds the maximum size of %d MB.',
            $filename,
            (int) round($maxKilobytes / 1024),
        );
    }

    public static function combinedRequestExceededMessage(): string
    {
        return sprintf(
            'The combined upload size exceeds the maximum request limit of %d MB.',
            (int) round(self::DOCUMENTED_POST_MAX_BYTES / (1024 * 1024)),
        );
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    public static function combinedUploadBytes(array $files): int
    {
        $total = 0;

        foreach ($files as $file) {
            $total += $file->getSize();
        }

        return $total;
    }

    public static function validateProofFile(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $maxKilobytes = self::maxKilobytesForProofExtension($extension);

        if ($maxKilobytes === null) {
            return sprintf('"%s" is not an allowed proof file type.', $file->getClientOriginalName());
        }

        if ($file->getSize() > ($maxKilobytes * 1024)) {
            return self::sizeExceededMessage($file->getClientOriginalName(), $maxKilobytes);
        }

        return null;
    }

    public static function validateLogoFile(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::imageExtensions(), true)) {
            return sprintf('"%s" is not an allowed logo file type.', $file->getClientOriginalName());
        }

        if ($file->getSize() > (self::IMAGE_MAX_KILOBYTES * 1024)) {
            return self::sizeExceededMessage($file->getClientOriginalName(), self::IMAGE_MAX_KILOBYTES);
        }

        return null;
    }
}
