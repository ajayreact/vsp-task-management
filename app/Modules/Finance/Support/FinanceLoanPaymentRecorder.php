<?php

namespace App\Modules\Finance\Support;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceExpenseCategory;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Enums\FinanceLoanPaymentType;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceLoan;
use App\Modules\Finance\Models\FinanceLoanPayment;
use Illuminate\Support\Facades\DB;

final class FinanceLoanPaymentRecorder
{
    /**
     * @param  array{payment_date: string, amount: float|string, payment_type: string, note?: string|null}  $payload
     * @return array{payment: FinanceLoanPayment, expense: FinanceExpense, loan: FinanceLoan}
     */
    public function record(User $user, FinanceLoan $loan, array $payload): array
    {
        return DB::transaction(function () use ($user, $loan, $payload) {
            $amount = round((float) $payload['amount'], 2);
            $paymentType = FinanceLoanPaymentType::from((string) $payload['payment_type']);

            // Auto-label as full repayment when the amount clears the outstanding balance.
            if ($amount >= (float) $loan->remaining_amount - 0.00001) {
                $paymentType = FinanceLoanPaymentType::Full;
            }

            $loan->applyPayment(
                $amount,
                advanceEmiSchedule: in_array($paymentType, [FinanceLoanPaymentType::Emi, FinanceLoanPaymentType::Full], true),
            );
            $loan->save();

            /** @var FinanceLoanPayment $payment */
            $payment = FinanceLoanPayment::query()->create([
                'user_id' => $user->id,
                'fin_loan_id' => $loan->id,
                'payment_date' => $payload['payment_date'],
                'amount' => $amount,
                'payment_type' => $paymentType,
                'remaining_balance_after' => $loan->remaining_amount,
                'note' => $payload['note'] ?? null,
            ]);

            $description = $paymentType->expenseDescriptionPrefix().' – '.$loan->lender_name;
            if ($loan->reason !== '') {
                $description .= ' ('.$loan->reason.')';
            }

            /** @var FinanceExpense $expense */
            $expense = FinanceExpense::query()->create([
                'user_id' => $user->id,
                'fin_loan_id' => $loan->id,
                'fin_loan_payment_id' => $payment->id,
                'expense_date' => $payload['payment_date'],
                'category' => FinanceExpenseCategory::LoanPayment,
                'description' => $description,
                'amount' => $amount,
                'payment_status' => FinanceExpensePaymentStatus::Paid,
                'notes' => $payload['note'] ?? null,
                'excluded_as_liability' => false,
            ]);

            return [
                'payment' => $payment,
                'expense' => $expense,
                'loan' => $loan->fresh(),
            ];
        });
    }
}
