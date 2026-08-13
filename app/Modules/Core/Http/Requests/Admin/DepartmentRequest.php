<?php

namespace App\Modules\Core\Http\Requests\Admin;

use App\Modules\Core\Models\Department;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
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
        $department = $this->route('department');
        $departmentId = $department instanceof Department ? $department->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:32', 'alpha_dash',
                Rule::unique('departments', 'code')->ignore($departmentId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'parent_id' => [
                'nullable', 'integer',
                Rule::exists('departments', 'id'),
                Rule::notIn(array_filter([$departmentId])),
            ],
            'head_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'parent_id.not_in' => 'A department cannot be its own parent.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper($this->string('code')->trim()->value())]);
        }
    }
}
