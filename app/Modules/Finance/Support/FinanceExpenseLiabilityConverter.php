<?php

namespace App\Modules\Finance\Support;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Enums\FinanceLoanType;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceLoan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class FinanceExpenseLiabilityConverter
{
    /**
     * Convert a misfiled expense into a loan liability without deleting the expense.
     * The expense is excluded from expense totals and linked to the new loan.
     *
     * @param  array{loan_type?: string, lender_name?: string, reason?: string, confirm: bool}  $options
     */
    public function convert(User $user, FinanceExpense $expense, array $options): FinanceLoan
    {
        if (! ($options['confirm'] ?? false)) {
            throw ValidationException::withMessages([
                'confirm' => 'Please confirm you want to convert this expense into a loan liability.',
            ]);
        }

        if ($expense->user_id !== $user->id) {
            abort(403);
        }

        if ($expense->excluded_as_liability || $expense->converted_to_fin_loan_id) {
            throw ValidationException::withMessages([
                'expense' => 'This expense was already converted to a loan.',
            ]);
        }

        if ($expense->fin_loan_payment_id) {
            throw ValidationException::withMessages([
                'expense' => 'Loan-linked EMI expenses cannot be converted.',
            ]);
        }

        return DB::transaction(function () use ($user, $expense, $options) {
            $loanType = FinanceLoanType::tryFrom((string) ($options['loan_type'] ?? 'other')) ?? FinanceLoanType::Other;

            $loan = FinanceLoan::query()->create([
                ...FinanceLoan::normalizedAttributes([
                    'loan_type' => $loanType->value,
                    'loan_date' => $expense->expense_date->toDateString(),
                    'lender_name' => $options['lender_name'] ?? $expense->description,
                    'mobile_number' => null,
                    'reason' => $options['reason'] ?? $expense->description,
                    'loan_amount' => (float) $expense->amount,
                    'amount_paid' => 0,
                    'due_date' => null,
                    'emi_amount' => null,
                    'emi_due_day' => null,
                    'status' => FinanceLoanStatus::Active->value,
                    'notes' => trim('Converted from expense #'.$expense->id.($expense->notes ? ' — '.$expense->notes : '')),
                ]),
                'user_id' => $user->id,
            ]);

            $expense->update([
                'excluded_as_liability' => true,
                'converted_to_fin_loan_id' => $loan->id,
                'notes' => trim(($expense->notes ? $expense->notes."\n" : '').'Converted to loan #'.$loan->id.' (excluded from expense totals).'),
            ]);

            return $loan;
        });
    }

    public function revert(User $user, FinanceExpense $expense): void
    {
        if ($expense->user_id !== $user->id) {
            abort(403);
        }

        if (! $expense->excluded_as_liability || ! $expense->converted_to_fin_loan_id) {
            throw ValidationException::withMessages([
                'expense' => 'This expense is not a converted liability.',
            ]);
        }

        DB::transaction(function () use ($expense): void {
            $loan = FinanceLoan::query()->find($expense->converted_to_fin_loan_id);

            if ($loan && $loan->payments()->exists()) {
                throw ValidationException::withMessages([
                    'expense' => 'Cannot revert: the linked loan already has payments.',
                ]);
            }

            $loanId = $expense->converted_to_fin_loan_id;
            $expense->update([
                'excluded_as_liability' => false,
                'converted_to_fin_loan_id' => null,
            ]);

            if ($loan) {
                $loan->delete();
            }

            // Keep a short audit note without hard-deleting expense history.
            $expense->update([
                'notes' => trim(($expense->notes ? $expense->notes."\n" : '').'Reverted conversion from loan #'.$loanId.'.'),
            ]);
        });
    }
}
