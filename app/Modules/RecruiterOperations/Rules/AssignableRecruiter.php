<?php

namespace App\Modules\RecruiterOperations\Rules;

use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Services\RecruiterDirectory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The chosen employee is an active recruiter: active employment, an active
 * internal login, and recruiter.access.
 */
class AssignableRecruiter implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $employee = is_numeric($value) ? Employee::query()->find((int) $value) : null;

        if ($employee === null || ! app(RecruiterDirectory::class)->isAssignable($employee)) {
            $fail('Choose an active recruiter.');
        }
    }
}
