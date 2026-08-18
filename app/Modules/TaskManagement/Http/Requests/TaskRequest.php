<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\TaskPriority;
use App\Modules\TaskManagement\Enums\TaskType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Covers the descriptive fields for create/update. On create, an optional assignee
 * may be supplied; when present it is applied through TaskWorkflow after the draft
 * is saved, never by mass assignment on the model.
 */
class TaskRequest extends FormRequest
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
        $rules = [
            'tm_project_id' => [
                'required', 'integer',
                Rule::exists('tm_projects', 'id')->whereIn('status', ['planning', 'active']),
            ],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'type' => ['required', Rule::enum(TaskType::class)],
            'priority' => ['required', Rule::enum(TaskPriority::class)],
            'estimated_hours' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'due_at' => ['nullable', 'date'],
        ];

        if ($this->isMethod('post') && $this->routeIs('tasks.store')) {
            $rules['assigned_employee_id'] = ['nullable', 'integer', Rule::exists('employees', 'id')];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tm_project_id.exists' => 'Pick a project that is still open for new work.',
        ];
    }
}
