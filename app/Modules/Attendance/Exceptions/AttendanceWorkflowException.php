<?php

namespace App\Modules\Attendance\Exceptions;

use Exception;

class AttendanceWorkflowException extends Exception
{
    public static function alreadyCheckedIn(): self
    {
        return new self('You have already checked in today.');
    }

    public static function mustCheckInFirst(): self
    {
        return new self('You must check in before checking out.');
    }

    public static function alreadyCheckedOut(): self
    {
        return new self('You have already checked out today.');
    }

    public static function locationVerificationFailed(string $message): self
    {
        return new self($message);
    }

    public static function alreadyOnBreak(): self
    {
        return new self('You are already on a break.');
    }

    public static function mustBeOnBreakToResume(): self
    {
        return new self('You must be on a break to resume work.');
    }

    public static function mustEndBreakBeforeCheckOut(): self
    {
        return new self('End your break before checking out.');
    }

    public static function wfhNotApproved(): self
    {
        return new self('Work From Home is not approved for today.');
    }
}
