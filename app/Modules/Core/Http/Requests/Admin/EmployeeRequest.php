<?php

namespace App\Modules\Core\Http\Requests\Admin;

use App\Modules\Core\Enums\EmployeeStatus;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeRequest extends FormRequest
{
    /**
     * Authorization is handled by the controller's authorizeResource call.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeId = $employee instanceof Employee ? $employee->id : null;
        $userId = $employee instanceof Employee ? $employee->user_id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                $this->isMethod('POST') ? 'required' : 'nullable',
                'confirmed',
                Password::defaults(),
            ],
            'employee_code' => [
                'required', 'string', 'max:32',
                Rule::unique('employees', 'employee_code')->ignore($employeeId),
            ],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'designation' => ['nullable', 'string', 'max:255'],
            'reporting_to_id' => [
                'nullable', 'integer',
                Rule::exists('employees', 'id'),
                // Self-reporting would make the reporting chain a cycle of one.
                Rule::notIn(array_filter([$employeeId])),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'joined_on' => ['nullable', 'date'],
            'exited_on' => ['nullable', 'date', 'after_or_equal:joined_on'],
            'status' => ['required', Rule::enum(EmployeeStatus::class)],
            'is_active' => ['required', 'boolean'],
            'roles' => ['array'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name'),
                // Staff screens must never hand out the portal role, and
                // super-admin is granted through the seeder only.
                Rule::notIn([SystemRole::Client->value, SystemRole::SuperAdmin->value]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reporting_to_id.not_in' => 'An employee cannot report to themselves.',
            'roles.*.not_in' => 'That role cannot be assigned from this screen.',
        ];
    }
}
