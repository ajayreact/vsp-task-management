<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceIncomeStatus;
use App\Modules\Finance\Http\Requests\FinanceIncomeRequest;
use App\Modules\Finance\Models\FinanceIncome;
use App\Support\Pagination;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceIncomeController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FinanceIncome::class);

        $filters = $this->listFilters($request);
        $user = $request->user();

        $incomes = $this->filteredQuery($user, $filters)
            ->orderByDesc('income_date')
            ->orderByDesc('id')
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (FinanceIncome $income) => $this->summarise($income));

        $summaryBase = $this->summaryQuery($user, $filters);

        return Inertia::render('Finance/income/index', [
            'incomes' => $incomes,
            'filters' => $filters,
            'statuses' => FinanceIncomeStatus::options(),
            'summaries' => [
                'total' => (float) (clone $summaryBase)
                    ->where('status', '!=', FinanceIncomeStatus::Cancelled->value)
                    ->sum('amount'),
                'received' => (float) (clone $summaryBase)
                    ->where('status', FinanceIncomeStatus::Received->value)
                    ->sum('amount'),
                'pending' => (float) (clone $summaryBase)
                    ->where('status', FinanceIncomeStatus::Pending->value)
                    ->sum('amount'),
            ],
            'has_any_records' => FinanceIncome::query()->forUser($user)->exists(),
            'open_create' => $request->boolean('create'),
        ]);
    }

    public function store(FinanceIncomeRequest $request): RedirectResponse
    {
        $this->authorize('create', FinanceIncome::class);

        FinanceIncome::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.finance.income.index')
            ->with('success', 'Income recorded.');
    }

    public function update(FinanceIncomeRequest $request, FinanceIncome $income): RedirectResponse
    {
        $this->authorize('update', $income);

        $income->update($request->validated());

        return redirect()
            ->route('admin.finance.income.index')
            ->with('success', 'Income updated.');
    }

    public function destroy(Request $request, FinanceIncome $income): RedirectResponse
    {
        $this->authorize('delete', $income);

        $income->delete();

        return redirect()
            ->route('admin.finance.income.index')
            ->with('success', 'Income deleted.');
    }

    /**
     * @return array{search: string, status: string, date_from: string, date_to: string}
     */
    protected function listFilters(Request $request): array
    {
        return [
            'search' => $request->string('search')->trim()->value(),
            'status' => $request->string('status')->trim()->value(),
            'date_from' => $request->string('date_from')->trim()->value(),
            'date_to' => $request->string('date_to')->trim()->value(),
        ];
    }

    /**
     * @param  array{search: string, status: string, date_from: string, date_to: string}  $filters
     */
    protected function filteredQuery(User $user, array $filters): Builder
    {
        return $this->summaryQuery($user, $filters)
            ->when(
                $filters['status'] !== '' && FinanceIncomeStatus::tryFrom($filters['status']),
                fn (Builder $query) => $query->where('status', $filters['status']),
            );
    }

    /**
     * Summary cards respect search + date filters, but ignore status so all buckets stay visible.
     *
     * @param  array{search: string, status: string, date_from: string, date_to: string}  $filters
     */
    protected function summaryQuery(User $user, array $filters): Builder
    {
        return FinanceIncome::query()
            ->forUser($user)
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search) {
                    $query->where('person_name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($filters['date_from'] !== '', fn (Builder $query) => $query->whereDate('income_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $query) => $query->whereDate('income_date', '<=', $filters['date_to']));
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(FinanceIncome $income): array
    {
        return [
            'id' => $income->id,
            'income_date' => $income->income_date->toDateString(),
            'person_name' => $income->person_name,
            'mobile_number' => $income->mobile_number,
            'reason' => $income->reason,
            'amount' => (float) $income->amount,
            'status' => $income->status instanceof FinanceIncomeStatus
                ? $income->status->value
                : (string) $income->status,
            'notes' => $income->notes,
        ];
    }
}
