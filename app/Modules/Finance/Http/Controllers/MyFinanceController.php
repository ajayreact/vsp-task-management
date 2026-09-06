<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Enums\FinanceIncomeStatus;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceIncome;
use App\Modules\Finance\Models\FinanceLoan;
use App\Modules\Finance\Models\FinanceLoanPayment;
use App\Modules\Finance\Support\FinanceDatePeriod;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MyFinanceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewMyFinance');

        $user = $request->user();
        $period = FinanceDatePeriod::resolve($request, FinanceDatePeriod::THIS_MONTH);

        $incomeQuery = $this->scopedByDate(
            FinanceIncome::query()->forUser($user),
            'income_date',
            $period,
        );
        $expenseQuery = $this->scopedByDate(
            FinanceExpense::query()->forUser($user),
            'expense_date',
            $period,
        );
        $loanQuery = $this->scopedByDate(
            FinanceLoan::query()->forUser($user),
            'loan_date',
            $period,
        );
        $paymentQuery = $this->scopedByDate(
            FinanceLoanPayment::query()->forUser($user),
            'payment_date',
            $period,
        );

        $totalIncome = (float) (clone $incomeQuery)
            ->where('status', '!=', FinanceIncomeStatus::Cancelled->value)
            ->sum('amount');
        $totalReceived = (float) (clone $incomeQuery)
            ->where('status', FinanceIncomeStatus::Received->value)
            ->sum('amount');
        $totalExpenses = (float) (clone $expenseQuery)->sum('amount');
        $paidExpenses = (float) (clone $expenseQuery)
            ->where('payment_status', FinanceExpensePaymentStatus::Paid->value)
            ->sum('amount');
        $loanCount = (int) (clone $loanQuery)
            ->where('status', '!=', FinanceLoanStatus::Cancelled->value)
            ->count();
        $loanPaid = (float) (clone $loanQuery)
            ->where('status', '!=', FinanceLoanStatus::Cancelled->value)
            ->sum('amount_paid');
        $loanRemaining = (float) (clone $loanQuery)
            ->where('status', '!=', FinanceLoanStatus::Cancelled->value)
            ->sum('remaining_amount');
        $loanPaymentsTotal = (float) (clone $paymentQuery)->sum('amount');

        return Inertia::render('Finance/index', [
            'period' => $period,
            'period_options' => FinanceDatePeriod::options(),
            'summaries' => [
                'total_income' => $totalIncome,
                'total_received' => $totalReceived,
                'total_expenses' => $totalExpenses,
                'paid_expenses' => $paidExpenses,
                'total_loans' => $loanCount,
                'loan_paid' => $loanPaid,
                'loan_remaining' => $loanRemaining,
                'net_balance' => round($totalReceived - $paidExpenses, 2),
            ],
            'overview' => [
                'income' => $totalReceived,
                'expenses' => $paidExpenses,
                'loan_payments' => $loanPaymentsTotal,
            ],
            'counts' => [
                'income' => FinanceIncome::query()->forUser($user)->count(),
                'expenses' => FinanceExpense::query()->forUser($user)->count(),
                'loans' => FinanceLoan::query()->forUser($user)->count(),
            ],
            'recent_activity' => $this->recentActivity($user, $period),
            'loan_alerts' => $this->loanAlerts($user),
        ]);
    }

    /**
     * @param  array{period: string, date_from: string|null, date_to: string|null}  $period
     */
    protected function scopedByDate(Builder $query, string $column, array $period): Builder
    {
        return $query
            ->when($period['date_from'], fn (Builder $query, string $from) => $query->whereDate($column, '>=', $from))
            ->when($period['date_to'], fn (Builder $query, string $to) => $query->whereDate($column, '<=', $to));
    }

    /**
     * @param  array{period: string, date_from: string|null, date_to: string|null}  $period
     * @return list<array<string, mixed>>
     */
    protected function recentActivity($user, array $period): array
    {
        $incomes = $this->scopedByDate(FinanceIncome::query()->forUser($user), 'income_date', $period)
            ->orderByDesc('income_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (FinanceIncome $income) => [
                'id' => 'income-'.$income->id,
                'date' => $income->income_date->toDateString(),
                'type' => 'income',
                'type_label' => 'Income',
                'label' => $income->person_name.' · '.$income->reason,
                'amount' => (float) $income->amount,
                'status' => $income->status instanceof FinanceIncomeStatus
                    ? $income->status->value
                    : (string) $income->status,
                'status_label' => $income->status instanceof FinanceIncomeStatus
                    ? $income->status->label()
                    : (string) $income->status,
                'href' => route('admin.finance.income.index'),
            ]);

        $expenses = $this->scopedByDate(FinanceExpense::query()->forUser($user), 'expense_date', $period)
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (FinanceExpense $expense) => [
                'id' => 'expense-'.$expense->id,
                'date' => $expense->expense_date->toDateString(),
                'type' => 'expense',
                'type_label' => 'Expense',
                'label' => $expense->description,
                'amount' => (float) $expense->amount,
                'status' => $expense->payment_status instanceof FinanceExpensePaymentStatus
                    ? $expense->payment_status->value
                    : (string) $expense->payment_status,
                'status_label' => $expense->payment_status instanceof FinanceExpensePaymentStatus
                    ? $expense->payment_status->label()
                    : (string) $expense->payment_status,
                'href' => route('admin.finance.expenses.index'),
            ]);

        $payments = $this->scopedByDate(
            FinanceLoanPayment::query()->forUser($user)->with('loan'),
            'payment_date',
            $period,
        )
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (FinanceLoanPayment $payment) => [
                'id' => 'payment-'.$payment->id,
                'date' => $payment->payment_date->toDateString(),
                'type' => 'loan_payment',
                'type_label' => 'Loan Payment',
                'label' => ($payment->loan?->lender_name ?? 'Loan').($payment->note ? ' · '.$payment->note : ''),
                'amount' => (float) $payment->amount,
                'status' => 'paid',
                'status_label' => 'Paid',
                'href' => route('admin.finance.loans.index'),
            ]);

        return Collection::make()
            ->concat($incomes)
            ->concat($expenses)
            ->concat($payments)
            ->sortByDesc(fn (array $row) => $row['date'].'-'.$row['id'])
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function loanAlerts($user): array
    {
        $today = now()->startOfDay();
        $soon = now()->addDays(7)->endOfDay();

        return FinanceLoan::query()
            ->forUser($user)
            ->where('status', '!=', FinanceLoanStatus::Cancelled->value)
            ->where('remaining_amount', '>', 0)
            ->orderByRaw('case when due_date is null then 1 else 0 end')
            ->orderBy('due_date')
            ->limit(8)
            ->get()
            ->map(function (FinanceLoan $loan) use ($today, $soon) {
                $due = $loan->due_date?->startOfDay();
                $alert = 'remaining';
                $alertLabel = 'Remaining balance';

                if ($loan->status === FinanceLoanStatus::Overdue || ($due && $due->lt($today))) {
                    $alert = 'overdue';
                    $alertLabel = 'Overdue';
                } elseif ($due && $due->lte($soon)) {
                    $alert = 'due_soon';
                    $alertLabel = 'Due soon';
                }

                return [
                    'id' => $loan->id,
                    'lender_name' => $loan->lender_name,
                    'reason' => $loan->reason,
                    'due_date' => $loan->due_date?->toDateString(),
                    'remaining_amount' => (float) $loan->remaining_amount,
                    'status' => $loan->status instanceof FinanceLoanStatus
                        ? $loan->status->value
                        : (string) $loan->status,
                    'alert' => $alert,
                    'alert_label' => $alertLabel,
                ];
            })
            ->all();
    }
}
