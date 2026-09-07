<?php

namespace App\Modules\Finance\Support;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Enums\FinanceIncomeStatus;
use App\Modules\Finance\Enums\FinanceLoanPaymentType;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceIncome;
use App\Modules\Finance\Models\FinanceLoan;
use App\Modules\Finance\Models\FinanceLoanPayment;
use App\Modules\Finance\Models\FinanceRecurringExpense;
use Illuminate\Support\Carbon;

final class FinanceMonthlySummary
{
    /**
     * @param  array{period: string, date_from: string|null, date_to: string|null}  $period
     * @return array<string, float|int>
     */
    public function forUser(User $user, array $period): array
    {
        $from = $period['date_from'] ? Carbon::parse($period['date_from'])->startOfDay() : null;
        $to = $period['date_to'] ? Carbon::parse($period['date_to'])->endOfDay() : null;

        $incomeQuery = FinanceIncome::query()->forUser($user);
        $expenseQuery = FinanceExpense::query()->forUser($user)->countable();

        if ($from) {
            $incomeQuery->whereDate('income_date', '>=', $from->toDateString());
            $expenseQuery->whereDate('expense_date', '>=', $from->toDateString());
        }
        if ($to) {
            $incomeQuery->whereDate('income_date', '<=', $to->toDateString());
            $expenseQuery->whereDate('expense_date', '<=', $to->toDateString());
        }

        $receivedIncome = (float) (clone $incomeQuery)
            ->where('status', FinanceIncomeStatus::Received->value)
            ->sum('amount');

        $paidExpenses = (float) (clone $expenseQuery)
            ->where('payment_status', FinanceExpensePaymentStatus::Paid->value)
            ->sum('amount');

        $pendingExpenses = (float) (clone $expenseQuery)
            ->where('payment_status', FinanceExpensePaymentStatus::Pending->value)
            ->sum('amount');

        $emiDue = $this->emiDue($user, $from, $to);
        $recurringDue = $this->recurringDue($user, $from, $to);

        $loanOutstanding = (float) FinanceLoan::query()
            ->forUser($user)
            ->where('status', '!=', FinanceLoanStatus::Cancelled->value)
            ->sum('remaining_amount');

        return [
            'received_income' => $receivedIncome,
            'paid_expenses' => $paidExpenses,
            'pending_expenses' => $pendingExpenses,
            'emi_due' => $emiDue,
            'recurring_due' => $recurringDue,
            'total_monthly_commitments' => round($emiDue + $recurringDue, 2),
            'loan_outstanding' => $loanOutstanding,
            'net_balance' => round($receivedIncome - $paidExpenses, 2),
        ];
    }

    protected function emiDue(User $user, ?Carbon $from, ?Carbon $to): float
    {
        if ($from === null || $to === null) {
            // All-time view: no single-month EMI due concept — return 0.
            return 0.0;
        }

        $loans = FinanceLoan::query()
            ->forUser($user)
            ->where('status', '!=', FinanceLoanStatus::Cancelled->value)
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('emi_amount')
            ->where('emi_amount', '>', 0)
            ->whereNotNull('emi_due_day')
            ->get();

        $total = 0.0;

        foreach ($loans as $loan) {
            $dueDay = (int) $loan->emi_due_day;
            if ($dueDay < 1 || $dueDay > 28) {
                continue;
            }

            // EMI is considered due in this period if the due day falls within the range.
            $dueInPeriod = false;
            $cursor = $from->copy()->startOfMonth();
            while ($cursor->lte($to)) {
                $daysInMonth = $cursor->daysInMonth;
                $day = min($dueDay, $daysInMonth);
                $dueDate = $cursor->copy()->day($day);
                if ($dueDate->betweenIncluded($from, $to)) {
                    $dueInPeriod = true;
                    break;
                }
                $cursor->addMonthNoOverflow();
            }

            if (! $dueInPeriod) {
                continue;
            }

            $emiSatisfied = FinanceLoanPayment::query()
                ->forUser($user)
                ->where('fin_loan_id', $loan->id)
                ->whereDate('payment_date', '>=', $from->toDateString())
                ->whereDate('payment_date', '<=', $to->toDateString())
                ->whereIn('payment_type', [
                    FinanceLoanPaymentType::Emi->value,
                    FinanceLoanPaymentType::Full->value,
                ])
                ->exists();

            if ($emiSatisfied) {
                continue;
            }

            $total += (float) $loan->emi_amount;
        }

        return round($total, 2);
    }

    protected function recurringDue(User $user, ?Carbon $from, ?Carbon $to): float
    {
        if ($from === null || $to === null) {
            return 0.0;
        }

        $templates = FinanceRecurringExpense::query()
            ->forUser($user)
            ->active()
            ->get();

        $total = 0.0;

        foreach ($templates as $template) {
            if (! $template->isActiveInPeriod($from, $to)) {
                continue;
            }

            $dueDay = (int) $template->due_day;
            $dueInPeriod = false;
            $cursor = $from->copy()->startOfMonth();
            while ($cursor->lte($to)) {
                $daysInMonth = $cursor->daysInMonth;
                $day = min($dueDay, $daysInMonth);
                $dueDate = $cursor->copy()->day($day);
                if ($dueDate->betweenIncluded($from, $to)) {
                    $dueInPeriod = true;
                    break;
                }
                $cursor->addMonthNoOverflow();
            }

            if (! $dueInPeriod) {
                continue;
            }

            $alreadyPaid = FinanceExpense::query()
                ->forUser($user)
                ->countable()
                ->where('recurring_template_id', $template->id)
                ->where('payment_status', FinanceExpensePaymentStatus::Paid->value)
                ->whereDate('expense_date', '>=', $from->toDateString())
                ->whereDate('expense_date', '<=', $to->toDateString())
                ->exists();

            if ($alreadyPaid) {
                continue;
            }

            $total += (float) $template->amount;
        }

        return round($total, 2);
    }
}
