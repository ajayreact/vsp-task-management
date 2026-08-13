<?php

namespace App\Modules\TaskManagement\Http\Requests;

use App\Modules\TaskManagement\Enums\CompanyStatus;
use App\Modules\TaskManagement\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CompanyRequest extends FormRequest
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
        $company = $this->route('company');
        $companyId = $company instanceof Company ? $company->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:32', 'alpha_dash',
                Rule::unique('tm_companies', 'code')->ignore($companyId),
            ],
            'status' => ['required', Rule::enum(CompanyStatus::class)],
            'primary_contact_name' => ['nullable', 'string', 'max:255'],
            'primary_contact_email' => ['nullable', 'email', 'max:255'],
            'primary_contact_phone' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => strtoupper($this->string('code')->trim()->value())]);
        }
    }
}
