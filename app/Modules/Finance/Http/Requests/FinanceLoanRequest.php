<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Enums\FinanceLoanType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FinanceLoanRequest extends FormRequest
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
            'loan_date' => ['required', 'date'],
            'loan_type' => ['required', Rule::enum(FinanceLoanType::class)],
            'lender_name' => ['required', 'string', 'max:255'],
            'mobile_number' => ['nullable', 'string', 'max:20'],
            'reason' => ['required', 'string', 'max:255'],
            'loan_amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'amount_paid' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'emi_amount' => ['nullable', 'numeric', 'gt:0', 'max:9999999999.99'],
            'emi_due_day' => ['nullable', 'integer', 'min:1', 'max:28', 'required_with:emi_amount'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(FinanceLoanStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'loan_date' => 'date',
            'loan_type' => 'loan type',
            'lender_name' => 'loan name / lender',
            'mobile_number' => 'mobile number',
            'loan_amount' => 'original / loan amount',
            'amount_paid' => 'amount paid',
            'emi_amount' => 'monthly EMI',
            'emi_due_day' => 'EMI due day',
            'due_date' => 'due date',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('lender_name') && is_string($this->input('lender_name'))) {
            $this->merge(['lender_name' => trim($this->string('lender_name')->value())]);
        }

        if ($this->has('reason') && is_string($this->input('reason'))) {
            $this->merge(['reason' => trim($this->string('reason')->value())]);
        }

        if ($this->has('mobile_number') && is_string($this->input('mobile_number'))) {
            $mobile = trim($this->string('mobile_number')->value());
            $this->merge(['mobile_number' => $mobile === '' ? null : $mobile]);
        }

        if ($this->has('notes') && is_string($this->input('notes'))) {
            $notes = trim($this->string('notes')->value());
            $this->merge(['notes' => $notes === '' ? null : $notes]);
        }

        foreach (['due_date', 'emi_amount', 'emi_due_day'] as $field) {
            if ($this->has($field) && is_string($this->input($field)) && trim((string) $this->input($field)) === '') {
                $this->merge([$field => null]);
            }
        }

        if (! $this->filled('amount_paid')) {
            $this->merge(['amount_paid' => 0]);
        }

        if (! $this->filled('loan_type')) {
            $this->merge(['loan_type' => FinanceLoanType::Personal->value]);
        }
    }
}
