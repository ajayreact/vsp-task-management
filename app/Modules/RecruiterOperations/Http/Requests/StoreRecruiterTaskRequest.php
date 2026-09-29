<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Rules\AssignableRecruiter;
use App\Modules\RecruiterOperations\Services\RecruiterTaskService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A new recruiter task and the recruiter it goes to. The assignee is applied
 * by RecruiterTaskWorkflow, never by mass assignment.
 */
class StoreRecruiterTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...RecruiterTaskService::rules(),
            'assigned_employee_id' => ['required', 'integer', new AssignableRecruiter],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['assigned_employee_id' => 'recruiter'];
    }
}
