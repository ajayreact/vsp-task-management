<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\FinanceLoanType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ConvertExpenseToLoanRequest extends FormRequest
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
            'confirm' => ['accepted'],
            'loan_type' => ['required', Rule::enum(FinanceLoanType::class)],
            'lender_name' => ['nullable', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirm.accepted' => 'Please confirm you want to convert this expense into a loan liability.',
        ];
    }
}
