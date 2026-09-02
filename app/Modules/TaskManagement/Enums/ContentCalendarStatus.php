<?php

namespace App\Modules\TaskManagement\Enums;

enum ContentCalendarStatus: string
{
    case Draft = 'draft';
    case InProgress = 'in_progress';
    case Ready = 'ready';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case ChangesRequested = 'changes_requested';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::InProgress => 'In Progress',
            self::Ready => 'Ready',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::ChangesRequested => 'Changes Requested',
            self::Published => 'Published',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
