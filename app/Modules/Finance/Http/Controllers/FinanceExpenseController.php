<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceExpenseCategory;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Http\Requests\FinanceExpenseRequest;
use App\Modules\Finance\Models\FinanceExpense;
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
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (FinanceExpense $expense) => $this->summarise($expense));

        $summaryBase = $this->summaryQuery($user, $filters);

        return Inertia::render('Finance/expenses/index', [
            'expenses' => $expenses,
            'filters' => $filters,
            'categories' => FinanceExpenseCategory::options(),
            'payment_statuses' => FinanceExpensePaymentStatus::options(),
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
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.finance.expenses.index')
            ->with('success', 'Expense recorded.');
    }

    public function update(FinanceExpenseRequest $request, FinanceExpense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $expense->update($request->validated());

        return redirect()
            ->route('admin.finance.expenses.index')
            ->with('success', 'Expense updated.');
    }

    public function destroy(Request $request, FinanceExpense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return redirect()
            ->route('admin.finance.expenses.index')
            ->with('success', 'Expense deleted.');
    }

    /**
     * @return array{search: string, category: string, payment_status: string, date_from: string, date_to: string}
     */
    protected function listFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->value(),
            'category' => $request->string('category')->trim()->value(),
            'payment_status' => $request->string('payment_status')->trim()->value(),
            'date_from' => $request->string('date_from')->trim()->value(),
            'date_to' => $request->string('date_to')->trim()->value(),
        ];
    }

    /**
     * @param  array{search: string, category: string, payment_status: string, date_from: string, date_to: string}  $filters
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
     * @param  array{search: string, category: string, payment_status: string, date_from: string, date_to: string}  $filters
     */
    protected function summaryQuery(User $user, array $filters): Builder
    {
        return FinanceExpense::query()
            ->forUser($user)
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
        ];
    }
}
