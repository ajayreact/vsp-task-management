<?php

namespace App\Modules\Attendance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WfhAssignmentFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assign', \App\Modules\Attendance\Models\WfhRequest::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
