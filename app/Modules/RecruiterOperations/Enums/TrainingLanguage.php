<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * Languages a lesson can be read in. English is the canonical source: every
 * other language is a translation of the approved English lesson and is
 * never shown in its place unless it exists. Adding a language is a new case
 * here plus a voice for it in config/recruiter-training.php.
 */
enum TrainingLanguage: string
{
    case English = 'en';
    case Telugu = 'te';

    public static function default(): self
    {
        return self::English;
    }

    public function isCanonical(): bool
    {
        return $this === self::English;
    }

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Telugu => 'Telugu',
        };
    }

    /**
     * The name in the language itself, for the language picker.
     */
    public function nativeLabel(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Telugu => 'తెలుగు',
        };
    }

    /**
     * @return list<self>
     */
    public static function translations(): array
    {
        return array_values(array_filter(self::cases(), fn (self $language) => ! $language->isCanonical()));
    }

    /**
     * @return list<array{code: string, label: string, native_label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $language) => [
            'code' => $language->value,
            'label' => $language->label(),
            'native_label' => $language->nativeLabel(),
        ], self::cases());
    }
}
