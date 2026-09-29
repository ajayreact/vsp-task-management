<?php

namespace App\Modules\RecruiterOperations\Enums;

enum AssessmentResult: string
{
    case Passed = 'passed';
    case Failed = 'failed';
    case PendingReview = 'pending_review';

    public function label(): string
    {
        return match ($this) {
            self::Passed => 'Passed',
            self::Failed => 'Failed',
            self::PendingReview => 'Pending review',
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::PendingReview;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $result) => ['value' => $result->value, 'label' => $result->label()], self::cases());
    }
}
