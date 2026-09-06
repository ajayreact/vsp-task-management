<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Enums\FinanceExpenseCategory;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Enums\FinanceIncomeStatus;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceIncome;
use App\Modules\Finance\Models\FinanceLoan;
use App\Modules\Finance\Support\FinanceDatePeriod;
use App\Support\TabularExporter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceExportController extends Controller
{
    public function income(Request $request, TabularExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', FinanceIncome::class);

        $period = FinanceDatePeriod::resolve($request, FinanceDatePeriod::ALL);
        $rows = $this->scopedByDate(
            FinanceIncome::query()->forUser($request->user())->orderByDesc('income_date')->orderByDesc('id'),
            'income_date',
            $period,
        )->get()->map(fn (FinanceIncome $income) => [
            $income->income_date->toDateString(),
            $income->person_name,
            $income->mobile_number ?? '',
            $income->reason,
            (float) $income->amount,
            $income->status instanceof FinanceIncomeStatus ? $income->status->label() : (string) $income->status,
            $income->notes ?? '',
        ])->all();

        return $exporter->excel(
            'My Income',
            ['Date', 'Person Name', 'Mobile', 'Reason', 'Amount (INR)', 'Status', 'Notes'],
            $rows,
            'my-income-'.now()->format('Y-m-d-His'),
        );
    }

    public function expenses(Request $request, TabularExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', FinanceExpense::class);

        $period = FinanceDatePeriod::resolve($request, FinanceDatePeriod::ALL);
        $rows = $this->scopedByDate(
            FinanceExpense::query()->forUser($request->user())->orderByDesc('expense_date')->orderByDesc('id'),
            'expense_date',
            $period,
        )->get()->map(fn (FinanceExpense $expense) => [
            $expense->expense_date->toDateString(),
            $expense->category instanceof FinanceExpenseCategory ? $expense->category->label() : (string) $expense->category,
            $expense->description,
            (float) $expense->amount,
            $expense->payment_status instanceof FinanceExpensePaymentStatus
                ? $expense->payment_status->label()
                : (string) $expense->payment_status,
            $expense->notes ?? '',
        ])->all();

        return $exporter->excel(
            'My Expenses',
            ['Date', 'Category', 'Description', 'Amount (INR)', 'Payment Status', 'Notes'],
            $rows,
            'my-expenses-'.now()->format('Y-m-d-His'),
        );
    }

    public function loans(Request $request, TabularExporter $exporter): StreamedResponse
    {
        $this->authorize('viewAny', FinanceLoan::class);

        $period = FinanceDatePeriod::resolve($request, FinanceDatePeriod::ALL);
        $rows = $this->scopedByDate(
            FinanceLoan::query()->forUser($request->user())->orderByDesc('loan_date')->orderByDesc('id'),
            'loan_date',
            $period,
        )->get()->map(fn (FinanceLoan $loan) => [
            $loan->loan_date->toDateString(),
            $loan->lender_name,
            $loan->mobile_number ?? '',
            $loan->reason,
            (float) $loan->loan_amount,
            (float) $loan->amount_paid,
            (float) $loan->remaining_amount,
            $loan->due_date?->toDateString() ?? '',
            $loan->status instanceof FinanceLoanStatus ? $loan->status->label() : (string) $loan->status,
            $loan->notes ?? '',
        ])->all();

        return $exporter->excel(
            'My Loans',
            ['Date', 'Lender', 'Mobile', 'Reason', 'Loan Amount (INR)', 'Paid (INR)', 'Remaining (INR)', 'Due Date', 'Status', 'Notes'],
            $rows,
            'my-loans-'.now()->format('Y-m-d-His'),
        );
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
}
