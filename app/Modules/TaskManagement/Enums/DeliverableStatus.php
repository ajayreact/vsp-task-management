<?php

namespace App\Modules\TaskManagement\Enums;

enum DeliverableStatus: string
{
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::InReview => 'In review',
            self::ChangesRequested => 'Changes requested',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Submitted, self::InReview], true);
    }
}
