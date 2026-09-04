<?php

namespace App\Modules\TaskManagement\Enums;

enum ContractStatus: string
{
    case Draft = 'draft';
    case Generated = 'generated';
    case SentToClient = 'sent_to_client';
    case Viewed = 'viewed';
    case Signed = 'signed';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Generated => 'Generated',
            self::SentToClient => 'Sent to Client',
            self::Viewed => 'Viewed',
            self::Signed => 'Signed',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'secondary',
            self::Generated => 'outline',
            self::SentToClient => 'default',
            self::Viewed => 'default',
            self::Signed => 'default',
            self::Rejected => 'destructive',
            self::Expired => 'destructive',
            self::Cancelled => 'destructive',
        };
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Generated], true);
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
