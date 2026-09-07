<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\FinanceExpenseCategory;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Models\FinanceRecurringExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FinanceExpenseRequest extends FormRequest
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
            'expense_date' => ['required', 'date'],
            'category' => ['required', Rule::enum(FinanceExpenseCategory::class)],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'payment_status' => ['required', Rule::enum(FinanceExpensePaymentStatus::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'recurring_template_id' => [
                'nullable',
                'integer',
                Rule::exists('fin_recurring_expenses', 'id')->where(fn ($query) => $query->where('user_id', $this->user()?->id)),
            ],
            'confirm_large_expense' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'expense_date' => 'date',
            'payment_status' => 'payment status',
            'recurring_template_id' => 'recurring template',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $amount = (float) $this->input('amount');
            $description = mb_strtolower((string) $this->input('description').' '.(string) $this->input('notes'));
            $looksLikeLoan = str_contains($description, 'gold loan')
                || str_contains($description, 'loan repayment')
                || (str_contains($description, 'loan') && ! str_contains($description, 'emi') && $amount >= 25000);

            if ($looksLikeLoan) {
                $validator->errors()->add(
                    'amount',
                    'This looks like a loan liability. Record it under Loans & Liabilities, not Expenses. Only EMI payments belong in Expenses.',
                );

                return;
            }

            if ($amount >= 50000 && ! $this->boolean('confirm_large_expense')) {
                $validator->errors()->add(
                    'confirm_large_expense',
                    'Amounts of ₹50,000+ are usually loan liabilities. Confirm this is an actual cash expense, or record it under Loans.',
                );
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function expenseAttributes(): array
    {
        $data = $this->safe()->only([
            'expense_date',
            'category',
            'description',
            'amount',
            'payment_status',
            'notes',
            'recurring_template_id',
        ]);

        if (empty($data['recurring_template_id'])) {
            $data['recurring_template_id'] = null;
        }

        return $data;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('description') && is_string($this->input('description'))) {
            $this->merge(['description' => trim($this->string('description')->value())]);
        }

        if ($this->has('notes') && is_string($this->input('notes'))) {
            $notes = trim($this->string('notes')->value());
            $this->merge(['notes' => $notes === '' ? null : $notes]);
        }

        if ($this->has('recurring_template_id') && $this->input('recurring_template_id') === '') {
            $this->merge(['recurring_template_id' => null]);
        }

        if ($this->has('confirm_large_expense')) {
            $this->merge(['confirm_large_expense' => $this->boolean('confirm_large_expense')]);
        }
    }
}
