<?php

namespace App\Modules\TaskManagement\Enums;

/**
 * The task lifecycle. Transitions live here rather than in controllers so that
 * every entry point — the board, the detail screen, the acceptance flow — is
 * bound by the same rules.
 */
enum TaskStatus: string
{
    case Draft = 'draft';
    case Open = 'open';
    case Assigned = 'assigned';
    /** @deprecated Legacy rows only — accept and claim now land on InProgress. */
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Revision = 'revision';
    /** @deprecated Legacy rows only — internal approval keeps the task in review. */
    case Approved = 'approved';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Open',
            self::Assigned => 'Assigned',
            self::Accepted => 'Accepted',
            self::InProgress => 'In progress',
            self::InReview => 'Under review',
            self::Revision => 'Changes requested',
            self::Approved => 'Approved',
            self::Completed => 'Completed',
        };
    }

    /**
     * @return array<int, self>
     */
    public function allowedNext(): array
    {
        return match ($this) {
            self::Draft => [self::Open, self::Assigned],
            self::Open => [self::Assigned, self::InProgress, self::Draft],
            self::Assigned => [self::InProgress, self::Open],
            self::Accepted => [self::InProgress],
            self::InProgress => [self::InReview],
            self::InReview => [self::Revision],
            self::Revision => [self::InReview],
            self::Approved => [self::Completed],
            self::Completed => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedNext(), strict: true);
    }

    /**
     * Nobody is working on it yet, so it can still be reassigned freely.
     */
    public function isUnstarted(): bool
    {
        return in_array($this, [self::Draft, self::Open, self::Assigned], strict: true);
    }

    public function isClosed(): bool
    {
        return $this === self::Completed;
    }

    /**
     * The timer and manual entries are allowed once work has started.
     */
    public function isWorkable(): bool
    {
        return in_array($this, [
            self::InProgress,
            self::InReview,
            self::Revision,
        ], true);
    }

    /**
     * Hours still sitting on this person for the workload board.
     */
    public function countsTowardWorkload(): bool
    {
        return in_array($this, [
            self::Assigned,
            self::InProgress,
            self::InReview,
            self::Revision,
        ], true);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status) => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
