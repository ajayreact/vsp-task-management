<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Rules\AssignableRecruiter;
use Illuminate\Foundation\Http\FormRequest;

class ReassignRecruiterTaskRequest extends FormRequest
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
            'assigned_employee_id' => ['required', 'integer', new AssignableRecruiter],
            'reason' => ['nullable', 'string', 'max:1000'],
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
