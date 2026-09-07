<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Enums\FinanceLoanPaymentType;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Models\FinanceLoan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FinanceLoanPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $loan = $this->route('loan');

        if (! $loan instanceof FinanceLoan) {
            return false;
        }

        return $this->user()?->can('recordPayment', $loan) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'payment_type' => ['required', Rule::enum(FinanceLoanPaymentType::class)],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'payment_date' => 'payment date',
            'amount' => 'repayment amount',
            'payment_type' => 'payment type',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $loan = $this->route('loan');

            if (! $loan instanceof FinanceLoan || $validator->errors()->isNotEmpty()) {
                return;
            }

            if ($loan->status === FinanceLoanStatus::Cancelled) {
                $validator->errors()->add('amount', 'Cannot record payments on a cancelled loan.');

                return;
            }

            $remaining = (float) $loan->remaining_amount;
            $amount = (float) $this->input('amount');
            $type = FinanceLoanPaymentType::tryFrom((string) $this->input('payment_type'));

            if ($remaining <= 0) {
                $validator->errors()->add('amount', 'This loan has no remaining balance.');

                return;
            }

            if ($amount > $remaining + 0.00001) {
                $validator->errors()->add(
                    'amount',
                    'Repayment cannot exceed the outstanding balance of ₹'.number_format($remaining, 2, '.', ',').'.',
                );

                return;
            }

            if ($type === FinanceLoanPaymentType::Full && abs($amount - $remaining) > 0.00001) {
                $validator->errors()->add(
                    'amount',
                    'Full repayment must equal the outstanding balance of ₹'.number_format($remaining, 2, '.', ',').'.',
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('note') && is_string($this->input('note'))) {
            $note = trim($this->string('note')->value());
            $this->merge(['note' => $note === '' ? null : $note]);
        }

        if (! $this->filled('payment_type')) {
            $this->merge(['payment_type' => FinanceLoanPaymentType::Partial->value]);
        }
    }
}
