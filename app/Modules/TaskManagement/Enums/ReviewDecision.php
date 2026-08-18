<?php

namespace App\Modules\TaskManagement\Enums;

enum ReviewDecision: string
{
    case Approve = 'approve';
    case RequestChanges = 'request_changes';
    case Reject = 'reject';

    public function label(): string
    {
        return match ($this) {
            self::Approve => 'Approve',
            self::RequestChanges => 'Request changes',
            self::Reject => 'Reject',
        };
    }

    public function resultingDeliverableStatus(): DeliverableStatus
    {
        return match ($this) {
            self::Approve => DeliverableStatus::Approved,
            self::RequestChanges => DeliverableStatus::ChangesRequested,
            self::Reject => DeliverableStatus::Rejected,
        };
    }

    public function resultingTaskStatus(): TaskStatus
    {
        return match ($this) {
            self::Approve => TaskStatus::Approved,
            self::RequestChanges, self::Reject => TaskStatus::Revision,
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $decision) => ['value' => $decision->value, 'label' => $decision->label()],
            self::cases(),
        );
    }
}
