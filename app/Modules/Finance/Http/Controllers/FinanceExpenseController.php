<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceExpenseCategory;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Enums\FinanceLoanType;
use App\Modules\Finance\Http\Requests\ConvertExpenseToLoanRequest;
use App\Modules\Finance\Http\Requests\FinanceExpenseRequest;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceRecurringExpense;
use App\Modules\Finance\Support\FinanceExpenseLiabilityConverter;
use App\Support\Pagination;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FinanceExpense::class);

        $filters = $this->listFilters($request);
        $user = $request->user();

        $expenses = $this->filteredQuery($user, $filters)
            ->with(['loan:id,lender_name', 'recurringTemplate:id,title'])
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (FinanceExpense $expense) => $this->summarise($expense));

        $summaryBase = $this->summaryQuery($user, $filters)->countable();

        return Inertia::render('Finance/expenses/index', [
            'expenses' => $expenses,
            'filters' => $filters,
            'categories' => FinanceExpenseCategory::options(),
            'payment_statuses' => FinanceExpensePaymentStatus::options(),
            'loan_types' => FinanceLoanType::options(),
            'recurring_templates' => FinanceRecurringExpense::query()
                ->forUser($user)
                ->active()
                ->orderBy('title')
                ->get(['id', 'title', 'amount', 'category'])
                ->map(fn (FinanceRecurringExpense $item) => [
                    'id' => $item->id,
                    'title' => $item->title,
                    'amount' => (float) $item->amount,
                    'category' => $item->category instanceof FinanceExpenseCategory
                        ? $item->category->value
                        : (string) $item->category,
                ]),
            'summaries' => [
                'total' => (float) (clone $summaryBase)->sum('amount'),
                'paid' => (float) (clone $summaryBase)
                    ->where('payment_status', FinanceExpensePaymentStatus::Paid->value)
                    ->sum('amount'),
                'pending' => (float) (clone $summaryBase)
                    ->where('payment_status', FinanceExpensePaymentStatus::Pending->value)
                    ->sum('amount'),
            ],
            'has_any_records' => FinanceExpense::query()->forUser($user)->exists(),
            'open_create' => $request->boolean('create'),
        ]);
    }

    public function store(FinanceExpenseRequest $request): RedirectResponse
    {
        $this->authorize('create', FinanceExpense::class);

        FinanceExpense::query()->create([
            ...$request->expenseAttributes(),
            'user_id' => $request->user()->id,
            'excluded_as_liability' => false,
        ]);

        return redirect()
            ->route('admin.finance.expenses.index')
            ->with('success', 'Expense recorded.');
    }

    public function update(FinanceExpenseRequest $request, FinanceExpense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        if ($expense->fin_loan_payment_id) {
            return back()->withErrors([
                'description' => 'EMI expenses created from loan payments cannot be edited here. Adjust the loan payment instead.',
            ]);
        }

        $expense->update($request->expenseAttributes());

        return redirect()
            ->route('admin.finance.expenses.index')
            ->with('success', 'Expense updated.');
    }

    public function destroy(Request $request, FinanceExpense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        if ($expense->fin_loan_payment_id) {
            return back()->withErrors([
                'expense' => 'EMI expenses linked to loan payments cannot be deleted directly.',
            ]);
        }

        $expense->delete();

        return redirect()
            ->route('admin.finance.expenses.index')
            ->with('success', 'Expense deleted.');
    }

    public function convertToLoan(
        ConvertExpenseToLoanRequest $request,
        FinanceExpense $expense,
        FinanceExpenseLiabilityConverter $converter,
    ): RedirectResponse {
        $this->authorize('update', $expense);

        $loan = $converter->convert($request->user(), $expense, [
            'confirm' => true,
            'loan_type' => $request->validated('loan_type'),
            'lender_name' => $request->validated('lender_name'),
            'reason' => $request->validated('reason'),
        ]);

        return redirect()
            ->route('admin.finance.loans.index')
            ->with('success', 'Expense converted to loan #'.$loan->id.'. It is excluded from expense totals.');
    }

    public function revertConversion(
        Request $request,
        FinanceExpense $expense,
        FinanceExpenseLiabilityConverter $converter,
    ): RedirectResponse {
        $this->authorize('update', $expense);

        $converter->revert($request->user(), $expense);

        return redirect()
            ->route('admin.finance.expenses.index')
            ->with('success', 'Conversion reverted. Expense counts in totals again.');
    }

    /**
     * @return array{search: string, category: string, payment_status: string, date_from: string, date_to: string, include_excluded: string}
     */
    protected function listFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->value(),
            'category' => $request->string('category')->trim()->value(),
            'payment_status' => $request->string('payment_status')->trim()->value(),
            'date_from' => $request->string('date_from')->trim()->value(),
            'date_to' => $request->string('date_to')->trim()->value(),
            'include_excluded' => $request->string('include_excluded')->trim()->value(),
        ];
    }

    /**
     * @param  array{search: string, category: string, payment_status: string, date_from: string, date_to: string, include_excluded: string}  $filters
     */
    protected function filteredQuery(User $user, array $filters): Builder
    {
        return $this->summaryQuery($user, $filters)
            ->when(
                $filters['category'] !== '' && FinanceExpenseCategory::tryFrom($filters['category']),
                fn (Builder $query) => $query->where('category', $filters['category']),
            )
            ->when(
                $filters['payment_status'] !== '' && FinanceExpensePaymentStatus::tryFrom($filters['payment_status']),
                fn (Builder $query) => $query->where('payment_status', $filters['payment_status']),
            );
    }

    /**
     * @param  array{search: string, category: string, payment_status: string, date_from: string, date_to: string, include_excluded: string}  $filters
     */
    protected function summaryQuery(User $user, array $filters): Builder
    {
        return FinanceExpense::query()
            ->forUser($user)
            ->when($filters['include_excluded'] !== '1', fn (Builder $query) => $query->countable())
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search) {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%");
                });
            })
            ->when($filters['date_from'] !== '', fn (Builder $query) => $query->whereDate('expense_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $query) => $query->whereDate('expense_date', '<=', $filters['date_to']));
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(FinanceExpense $expense): array
    {
        return [
            'id' => $expense->id,
            'expense_date' => $expense->expense_date->toDateString(),
            'category' => $expense->category instanceof FinanceExpenseCategory
                ? $expense->category->value
                : (string) $expense->category,
            'description' => $expense->description,
            'amount' => (float) $expense->amount,
            'payment_status' => $expense->payment_status instanceof FinanceExpensePaymentStatus
                ? $expense->payment_status->value
                : (string) $expense->payment_status,
            'notes' => $expense->notes,
            'fin_loan_id' => $expense->fin_loan_id,
            'fin_loan_payment_id' => $expense->fin_loan_payment_id,
            'recurring_template_id' => $expense->recurring_template_id,
            'loan_name' => $expense->loan?->lender_name,
            'recurring_title' => $expense->recurringTemplate?->title,
            'excluded_as_liability' => (bool) $expense->excluded_as_liability,
            'converted_to_fin_loan_id' => $expense->converted_to_fin_loan_id,
            'likely_liability' => $expense->isLikelyMisfiledLiability(),
            'is_emi_linked' => $expense->fin_loan_payment_id !== null,
        ];
    }
}
