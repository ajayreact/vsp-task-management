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
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Post Not Ready',
            self::InProgress => 'Content In Progress',
            self::Ready => 'Post Ready',
            self::UnderReview => 'Client Review',
            self::Approved => 'Client Approved',
            self::ChangesRequested => 'Client Changes Requested',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Rejected => 'Rejected',
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
