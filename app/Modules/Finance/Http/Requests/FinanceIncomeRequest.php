<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\FinanceIncomeStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewMyFinance') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'income_date' => ['required', 'date'],
            'person_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'reason' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'status' => ['required', Rule::enum(FinanceIncomeStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'income_date' => 'date',
            'person_name' => 'person name',
            'mobile_number' => 'mobile number',
            'amount' => 'amount',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('mobile_number') && is_string($this->input('mobile_number'))) {
            $mobile = trim($this->string('mobile_number')->value());
            $this->merge(['mobile_number' => $mobile === '' ? null : $mobile]);
        }

        if ($this->has('notes') && is_string($this->input('notes'))) {
            $notes = trim($this->string('notes')->value());
            $this->merge(['notes' => $notes === '' ? null : $notes]);
        }

        if ($this->has('person_name') && is_string($this->input('person_name'))) {
            $this->merge(['person_name' => trim($this->string('person_name')->value())]);
        }

        if ($this->has('reason') && is_string($this->input('reason'))) {
            $this->merge(['reason' => trim($this->string('reason')->value())]);
        }
    }
}
