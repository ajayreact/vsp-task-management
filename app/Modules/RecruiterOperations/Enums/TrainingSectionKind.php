<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * What a lesson section is for. The kind decides how the section is styled
 * (an example is a callout, a takeaway is highlighted); the heading is free
 * text, so "Call Flow" or "State Code Table" are ordinary Content sections.
 */
enum TrainingSectionKind: string
{
    case Objective = 'objective';
    case Content = 'content';
    case Example = 'example';
    case Reference = 'reference';
    case Mistakes = 'mistakes';
    case Practice = 'practice';
    case Note = 'note';
    case Takeaway = 'takeaway';

    public function label(): string
    {
        return match ($this) {
            self::Objective => 'Learning objective',
            self::Content => 'Content section',
            self::Example => 'Recruiter example',
            self::Reference => 'Quick reference / checklist',
            self::Mistakes => 'Common mistakes',
            self::Practice => 'Practice',
            self::Note => 'Important note',
            self::Takeaway => 'Key takeaway',
        };
    }

    public function defaultHeading(): string
    {
        return match ($this) {
            self::Objective => 'Learning Objective',
            self::Content => 'What You Need to Know',
            self::Example => 'Recruiter Example',
            self::Reference => 'Quick Reference',
            self::Mistakes => 'Common Mistakes',
            self::Practice => 'Practice',
            self::Note => 'Important Note',
            self::Takeaway => 'Key Takeaway',
        };
    }

    /**
     * @return list<array{value: string, label: string, heading: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $kind) => [
            'value' => $kind->value,
            'label' => $kind->label(),
            'heading' => $kind->defaultHeading(),
        ], self::cases());
    }
}
