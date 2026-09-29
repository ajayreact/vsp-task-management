<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * Assigned, InProgress and Completed are stored and written only by
 * TrainingProgressService. Overdue is never stored: it is an open assignment
 * whose due date has passed, worked out when read.
 */
enum TrainingAssignmentStatus: string
{
    case Assigned = 'assigned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Not started',
            self::InProgress => 'In progress',
            self::Completed => 'Completed',
            self::Overdue => 'Overdue',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Completed;
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
