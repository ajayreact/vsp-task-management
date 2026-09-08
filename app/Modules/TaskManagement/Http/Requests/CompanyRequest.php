<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\CompanyStatus;
use App\Modules\TaskManagement\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->route('company');

        if ($company instanceof Company) {
            return $this->user()?->can('update', $company) ?? false;
        }

        return $this->user()?->can('create', Company::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $company = $this->route('company');
        $companyId = $company instanceof Company ? $company->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:32', 'alpha_dash',
                Rule::unique('tm_companies', 'code')->ignore($companyId),
            ],
            'status' => ['required', Rule::enum(CompanyStatus::class)],
            'primary_responsible_employee_id' => [
                Rule::requiredIf(fn () => $this->input('status') === CompanyStatus::Active->value),
                'nullable',
                'integer',
                Rule::exists('employees', 'id'),
            ],
            'supporting_employee_ids' => ['nullable', 'array'],
            'supporting_employee_ids.*' => ['integer', 'distinct', Rule::exists('employees', 'id')],
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_email' => ['nullable', 'email:filter', 'max:255'],
            'primary_contact_phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'monthly_post_target' => ['nullable', 'integer', 'min:0', 'max:999'],
            'holiday_india_enabled' => ['sometimes', 'boolean'],
            'holiday_usa_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $primaryId = $this->integer('primary_responsible_employee_id') ?: null;
            $supporting = collect($this->input('supporting_employee_ids', []))
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->all();

            if ($primaryId !== null && in_array($primaryId, $supporting, true)) {
                $validator->errors()->add(
                    'supporting_employee_ids',
                    'The primary responsible employee cannot also be selected as a supporting employee.',
                );
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'primary_contact_email.email' => 'Enter a valid email address (for example name@company.com).',
            'code.unique' => 'This client code is already in use.',
            'code.alpha_dash' => 'Code may only contain letters, numbers, dashes, and underscores.',
            'primary_responsible_employee_id.required' => 'Select a primary responsible employee for an active client.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper($this->string('code')->trim()->value())]);
        }

        foreach (['primary_contact_name', 'primary_contact_email', 'primary_contact_phone', 'notes'] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim((string) $this->input($field)) === '') {
                $this->merge([$field => null]);
            }
        }

        if ($this->has('primary_responsible_employee_id') && $this->input('primary_responsible_employee_id') === '') {
            $this->merge(['primary_responsible_employee_id' => null]);
        }

        if ($this->has('supporting_employee_ids')) {
            $ids = collect($this->input('supporting_employee_ids', []))
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->all();

            $this->merge(['supporting_employee_ids' => $ids]);
        }

        if ($this->has('holiday_india_enabled')) {
            $this->merge(['holiday_india_enabled' => $this->boolean('holiday_india_enabled')]);
        }

        if ($this->has('holiday_usa_enabled')) {
            $this->merge(['holiday_usa_enabled' => $this->boolean('holiday_usa_enabled')]);
        }
    }
}
