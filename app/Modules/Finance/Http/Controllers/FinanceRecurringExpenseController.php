<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Enums\FinanceExpenseCategory;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Http\Requests\FinanceRecurringExpenseRequest;
use App\Modules\Finance\Models\FinanceRecurringExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceRecurringExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FinanceRecurringExpense::class);

        $user = $request->user();

        $recurring = FinanceRecurringExpense::query()
            ->forUser($user)
            ->orderByDesc('active')
            ->orderBy('due_day')
            ->orderBy('title')
            ->get()
            ->map(fn (FinanceRecurringExpense $item) => [
                'id' => $item->id,
                'title' => $item->title,
                'amount' => (float) $item->amount,
                'category' => $item->category instanceof FinanceExpenseCategory
                    ? $item->category->value
                    : (string) $item->category,
                'due_day' => $item->due_day,
                'active' => $item->active,
                'start_date' => $item->start_date->toDateString(),
                'end_date' => $item->end_date?->toDateString(),
                'notes' => $item->notes,
            ]);

        return Inertia::render('Finance/recurring/index', [
            'recurring' => $recurring,
            'categories' => FinanceExpenseCategory::options(),
            'payment_statuses' => FinanceExpensePaymentStatus::options(),
        ]);
    }

    public function store(FinanceRecurringExpenseRequest $request): RedirectResponse
    {
        $this->authorize('create', FinanceRecurringExpense::class);

        FinanceRecurringExpense::query()->create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.finance.recurring.index')
            ->with('success', 'Recurring expense saved.');
    }

    public function update(FinanceRecurringExpenseRequest $request, FinanceRecurringExpense $recurring): RedirectResponse
    {
        $this->authorize('update', $recurring);

        $recurring->update($request->validated());

        return redirect()
            ->route('admin.finance.recurring.index')
            ->with('success', 'Recurring expense updated.');
    }

    public function destroy(Request $request, FinanceRecurringExpense $recurring): RedirectResponse
    {
        $this->authorize('delete', $recurring);

        $recurring->delete();

        return redirect()
            ->route('admin.finance.recurring.index')
            ->with('success', 'Recurring expense removed.');
    }
}
