<?php

namespace App\Modules\RecruiterOperations\Http\Requests;

use App\Modules\RecruiterOperations\Rules\AssignableRecruiter;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Assign an assessment to one recruiter, several, or the whole recruiter
 * team. The version is always the assessment's current published one, chosen
 * on the server.
 */
class AssessmentAssignmentRequest extends FormRequest
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
        $mode = $this->input('mode');

        return [
            'assessment_id' => ['required', 'integer', 'exists:ro_assessments,id'],
            'mode' => ['required', 'in:individual,multiple,team'],
            'employee_ids' => $mode === 'team'
                ? ['prohibited']
                : ['required', 'array', 'min:1', 'max:500', ...($mode === 'individual' ? ['size:1'] : [])],
            'employee_ids.*' => ['integer', 'distinct', new AssignableRecruiter],
            'due_at' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'assessment_id' => 'assessment',
            'employee_ids' => 'recruiters',
            'employee_ids.*' => 'recruiter',
            'due_at' => 'due date',
        ];
    }
}
