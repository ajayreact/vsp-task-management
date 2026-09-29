<?php

namespace App\Modules\RecruiterOperations\Exceptions;

use App\Modules\RecruiterOperations\Enums\RecruiterTaskStatus;
use RuntimeException;

/**
 * A recruiter task workflow rule was broken. Controllers show it as a flash
 * message rather than an error page, because the usual cause is two people
 * acting on the same task at once.
 */
class RecruiterTaskWorkflowException extends RuntimeException
{
    public static function cannotTransition(RecruiterTaskStatus $from, RecruiterTaskStatus $to): self
    {
        return new self("A recruiter task cannot move from {$from->label()} to {$to->label()}.");
    }

    public static function notAssignee(): self
    {
        return new self('Only the recruiter this task is assigned to can do that.');
    }

    public static function notAssignableRecruiter(): self
    {
        return new self('That person is not an active recruiter who can be assigned tasks.');
    }

    public static function notReassignable(): self
    {
        return new self('Work has already started on this task, so it cannot be reassigned.');
    }

    public static function alreadyAssignedTo(): self
    {
        return new self('This task is already assigned to that recruiter.');
    }

    public static function notDeletable(): self
    {
        return new self('This task has workflow history, so it cannot be deleted. Cancel it instead.');
    }

    public static function cancelled(): self
    {
        return new self('This task has been cancelled and can no longer be changed.');
    }
}
