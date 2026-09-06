<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\FinanceLoan;
use Illuminate\Foundation\Http\FormRequest;
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
            'amount' => 'payment amount',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $loan = $this->route('loan');

            if (! $loan instanceof FinanceLoan || $validator->errors()->isNotEmpty()) {
                return;
            }

            if ($loan->status === \App\Modules\Finance\Enums\FinanceLoanStatus::Cancelled) {
                $validator->errors()->add('amount', 'Cannot record payments on a cancelled loan.');

                return;
            }

            $remaining = (float) $loan->remaining_amount;
            $amount = (float) $this->input('amount');

            if ($remaining <= 0) {
                $validator->errors()->add('amount', 'This loan has no remaining balance.');

                return;
            }

            if ($amount > $remaining + 0.00001) {
                $validator->errors()->add(
                    'amount',
                    'Payment cannot exceed the remaining balance of ₹'.number_format($remaining, 2).'.',
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
    }
}
