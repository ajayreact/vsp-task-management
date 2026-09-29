<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * The recruiter task lifecycle. There is no persisted "accepted" state:
 * accepting moves an assigned task straight to in progress. Reopening a
 * completed task is an event, not a status.
 */
enum RecruiterTaskStatus: string
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Assigned',
            self::InProgress => 'In progress',
            self::OnHold => 'On hold',
            self::Completed => 'Completed',
            self::Declined => 'Declined',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * @return list<self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Assigned => [self::InProgress, self::Declined, self::Cancelled],
            self::InProgress => [self::OnHold, self::Completed, self::Cancelled],
            self::OnHold => [self::InProgress, self::Cancelled],
            self::Declined => [self::Assigned, self::Cancelled],
            self::Completed => [self::InProgress],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNext(), true);
    }

    /**
     * Still somewhere in the lifecycle: not finished and not called off.
     */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Completed, self::Cancelled], true);
    }

    public function isTerminal(): bool
    {
        return $this === self::Cancelled;
    }

    /**
     * Work still sitting on the recruiter. A declined task is waiting on the
     * manager, not the recruiter, so it does not count.
     */
    public function countsAsPending(): bool
    {
        return in_array($this, [self::Assigned, self::InProgress, self::OnHold], true);
    }

    /**
     * Who holds the task can still change: nobody has started work on it.
     */
    public function isReassignable(): bool
    {
        return in_array($this, [self::Assigned, self::Declined], true);
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

    /**
     * @return list<string>
     */
    public static function pendingValues(): array
    {
        return array_values(array_map(
            fn (self $status) => $status->value,
            array_filter(self::cases(), fn (self $status) => $status->countsAsPending()),
        ));
    }
}
