<?php

namespace App\Modules\TaskManagement\Exceptions;

use RuntimeException;

/**
 * A time, timesheet or review rule was broken. Controllers turn this into a
 * flash message — two people clicking a timer at once is expected.
 */
class ProductivityException extends RuntimeException
{
    public static function timerAlreadyRunning(): self
    {
        return new self('You already have a timer running. Pause or stop it before starting another.');
    }

    public static function noRunningTimer(): self
    {
        return new self('There is no timer running on this task.');
    }

    public static function taskNotWorkable(): self
    {
        return new self('Time can only be logged once the task is in progress.');
    }

    public static function timesheetLocked(): self
    {
        return new self('That week has already been submitted, so the entries cannot change.');
    }

    public static function timesheetNotSubmittable(): self
    {
        return new self('Only a draft or rejected timesheet can be submitted.');
    }

    public static function timesheetNotReviewable(): self
    {
        return new self('Only a submitted timesheet can be approved or rejected.');
    }

    public static function cannotReviewOwnTimesheet(): self
    {
        return new self('You cannot approve or reject your own timesheet.');
    }

    public static function deliverableNotOpen(): self
    {
        return new self('This deliverable has already been decided.');
    }

    public static function cannotSubmitProof(): self
    {
        return new self('A proof can only be submitted while the task is in progress or in revision.');
    }

    public static function clientReviewUnavailable(): self
    {
        return new self('This deliverable is not ready for client review.');
    }
}
