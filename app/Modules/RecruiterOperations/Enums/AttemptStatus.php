<?php

namespace App\Modules\RecruiterOperations\Enums;

/**
 * An attempt is in progress until it is submitted (by the recruiter, or by
 * the server when time runs out). An attempt whose time ran out with nothing
 * answered is abandoned. Every attempt counts towards the attempt limit.
 */
enum AttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Abandoned = 'abandoned';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'In progress',
            self::Submitted => 'Submitted',
            self::Abandoned => 'Abandoned',
        };
    }
}
