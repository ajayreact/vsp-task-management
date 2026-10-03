<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * Compliance review of a lesson that touches immigration, work authorization
 * or other legal ground. Pending and Changes requested block publishing the
 * version; only a manager can approve, never the system.
 */
enum TrainingComplianceStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case ChangesRequested = 'changes_requested';
    case Approved = 'approved';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Not required',
            self::Pending => 'Pending review',
            self::ChangesRequested => 'Changes requested',
            self::Approved => 'Approved',
        };
    }

    public function isRequired(): bool
    {
        return $this !== self::NotRequired;
    }

    public function blocksPublishing(): bool
    {
        return $this === self::Pending || $this === self::ChangesRequested;
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $status) => ['value' => $status->value, 'label' => $status->label()], self::cases());
    }
}
