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
    case Accepted = 'accepted';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Revision = 'revision';
    case Approved = 'approved';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Open',
            self::Assigned => 'Awaiting acceptance',
            self::Accepted => 'Accepted',
            self::InProgress => 'In progress',
            self::InReview => 'In review',
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
            // Accepted is reachable directly because claiming is self-service:
            // the person chose the task, so there is nothing left to accept.
            self::Open => [self::Assigned, self::Accepted, self::Draft],
            // A decline returns the task to the board rather than stranding it
            // on someone who said no.
            self::Assigned => [self::Accepted, self::Open],
            self::Accepted => [self::InProgress],
            // Review is optional: not every task produces a creative that needs
            // approving, so work can close out directly.
            self::InProgress => [self::InReview, self::Completed],
            self::InReview => [self::Revision, self::Approved],
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
     * The timer and manual entries are allowed once someone has accepted the
     * work. Review rounds still count: people often keep the clock running
     * while they wait for a note.
     */
    public function isWorkable(): bool
    {
        return in_array($this, [
            self::Accepted,
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
            self::Accepted,
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
