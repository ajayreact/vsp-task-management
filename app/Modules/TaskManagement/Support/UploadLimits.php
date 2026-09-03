<?php

namespace App\Modules\TaskManagement\Support;

use Illuminate\Http\UploadedFile;

class UploadLimits
{
    public const MAX_FILE_KILOBYTES = 600 * 1024;

    public const MAX_FILE_BYTES = 600 * 1024 * 1024;

    public const TASK_ATTACHMENT_MAX_KILOBYTES = self::MAX_FILE_KILOBYTES;

    public const TASK_ATTACHMENT_MAX_FILES = 10;

    public const NOTIFICATION_SOUND_MAX_KILOBYTES = self::MAX_FILE_KILOBYTES;

    /** Documented production POST body limit used for multi-file validation. */
    public const DOCUMENTED_POST_MAX_BYTES = self::MAX_FILE_BYTES;

    public const SPATIE_MAX_BYTES = self::MAX_FILE_BYTES;

    public const MAX_FILE_MESSAGE = 'File size cannot exceed 600 MB.';

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

    public static function maxKilobytesForUploadedFile(): int
    {
        return self::MAX_FILE_KILOBYTES;
    }

    public static function maxKilobytesForProofExtension(string $extension): ?int
    {
        $extension = strtolower(ltrim($extension, '.'));

        if (! in_array($extension, self::proofExtensions(), true)) {
            return null;
        }

        return self::MAX_FILE_KILOBYTES;
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

    public static function sizeExceededMessage(?string $filename = null): string
    {
        if ($filename === null || $filename === '') {
            return self::MAX_FILE_MESSAGE;
        }

        return self::MAX_FILE_MESSAGE;
    }

    public static function combinedRequestExceededMessage(): string
    {
        return self::MAX_FILE_MESSAGE;
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

    public static function fileExceedsLimit(UploadedFile $file): bool
    {
        return $file->getSize() > self::MAX_FILE_BYTES;
    }

    public static function validateProofFile(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::proofExtensions(), true)) {
            return sprintf('"%s" is not an allowed proof file type.', $file->getClientOriginalName());
        }

        if (self::fileExceedsLimit($file)) {
            return self::sizeExceededMessage($file->getClientOriginalName());
        }

        return null;
    }

    public static function validateLogoFile(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::imageExtensions(), true)) {
            return sprintf('"%s" is not an allowed logo file type.', $file->getClientOriginalName());
        }

        if (self::fileExceedsLimit($file)) {
            return self::sizeExceededMessage($file->getClientOriginalName());
        }

        return null;
    }

    public static function validateDocumentFile(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, self::documentExtensions(), true)) {
            return sprintf('"%s" is not an allowed document file type.', $file->getClientOriginalName());
        }

        if (self::fileExceedsLimit($file)) {
            return self::sizeExceededMessage($file->getClientOriginalName());
        }

        return null;
    }

    public static function validateContentAttachmentFile(UploadedFile $file): ?string
    {
        return self::validateProofFile($file);
    }

    public static function validateTaskAttachmentFile(UploadedFile $file): ?string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $allowed = array_merge(
            self::imageExtensions(),
            ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'zip', 'txt', 'rtf'],
        );

        if (! in_array($extension, $allowed, true)) {
            return sprintf('"%s" is not an allowed working file type.', $file->getClientOriginalName());
        }

        if (self::fileExceedsLimit($file)) {
            return self::sizeExceededMessage($file->getClientOriginalName());
        }

        return null;
    }
}
