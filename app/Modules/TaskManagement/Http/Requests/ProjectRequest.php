<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\ProjectRole;
use App\Modules\TaskManagement\Enums\ProjectStatus;
use App\Modules\TaskManagement\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
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
        $project = $this->route('project');
        $projectId = $project instanceof Project ? $project->id : null;

        return [
            'tm_company_id' => ['required', 'integer', Rule::exists('tm_companies', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:32', 'alpha_dash',
                Rule::unique('tm_projects', 'code')->ignore($projectId),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'manager_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'budget_hours' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'members' => ['array'],
            'members.*.employee_id' => ['required', 'integer', 'distinct', Rule::exists('employees', 'id')],
            'members.*.project_role' => ['required', Rule::enum(ProjectRole::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'members.*.employee_id.distinct' => 'That person is already on the project.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper($this->string('code')->trim()->value())]);
        }
    }
}
