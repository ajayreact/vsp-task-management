<?php

namespace App\Modules\TaskManagement\Exceptions;

use App\Modules\TaskManagement\Enums\TaskStatus;
use RuntimeException;

/**
 * A rule of the workflow was broken. Controllers turn this into a flash message
 * rather than a 500, because the usual cause is two people acting on the same
 * task at once, which is expected rather than exceptional.
 */
class TaskWorkflowException extends RuntimeException
{
    public static function cannotTransition(TaskStatus $from, TaskStatus $to): self
    {
        return new self("A task cannot move from {$from->label()} to {$to->label()}.");
    }

    public static function alreadyClaimed(): self
    {
        return new self('Someone else claimed this task first.');
    }

    public static function notOnOffer(): self
    {
        return new self('This task is not waiting for your response.');
    }

    public static function alreadyStarted(): self
    {
        return new self('Work has already started on this task, so it cannot be reassigned.');
    }

    public static function employeeUnavailable(): self
    {
        return new self('That employee is not currently available for work.');
    }
}
