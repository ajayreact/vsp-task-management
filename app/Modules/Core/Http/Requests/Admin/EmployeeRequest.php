<?php

namespace App\Modules\Core\Http\Requests\Admin;

use App\Modules\Core\Enums\EmployeeStatus;
use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Employee;
use App\Services\EmployeeOfficeAssignmentService;
use Illuminate\Database\Query\Builder;
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

        $rules = [
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
            'department_id' => ['required', 'integer', Rule::exists('departments', 'id')],
            'designation_id' => ['required', 'integer', Rule::exists('designations', 'id')],
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
                // Super-admin is granted through the seeder only.
                Rule::notIn([SystemRole::SuperAdmin->value]),
            ],
        ];

        if ($this->user()?->can('viewAttendance')) {
            $currentOfficeId = $employee instanceof Employee
                ? app(EmployeeOfficeAssignmentService::class)->officeIdFor($employee->id)
                : null;

            $rules['office_location_id'] = [
                'nullable',
                'integer',
                Rule::exists('att_office_locations', 'id')->where(function (Builder $query) use ($currentOfficeId) {
                    $query->where(function (Builder $query) use ($currentOfficeId) {
                        $query->where('is_active', true);

                        if ($currentOfficeId !== null) {
                            $query->orWhere('id', $currentOfficeId);
                        }
                    });
                }),
            ];
        }

        return $rules;
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
