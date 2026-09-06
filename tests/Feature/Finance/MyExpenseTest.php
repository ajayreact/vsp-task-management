<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Finance\Enums\FinanceExpenseCategory;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Models\FinanceExpense;

beforeEach(function () {
    $this->withoutVite();
});

function makeExpense(\App\Modules\Core\Models\User $user, array $overrides = []): FinanceExpense
{
    return FinanceExpense::query()->create(array_merge([
        'user_id' => $user->id,
        'expense_date' => now()->toDateString(),
        'category' => FinanceExpenseCategory::Personal,
        'description' => 'Groceries',
        'amount' => 1200.50,
        'payment_status' => FinanceExpensePaymentStatus::Paid,
        'notes' => null,
    ], $overrides));
}

test('super admin can open my expenses page', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.finance.expenses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/expenses/index')
            ->where('summaries.total', 0)
            ->where('has_any_records', false)
            ->has('categories')
            ->has('payment_statuses', 2));
});

test('staff cannot open my expenses', function () {
    $this->actingAs(staffWith(Ability::ViewEmployees))
        ->get(route('admin.finance.expenses.index'))
        ->assertForbidden();
});

test('super admin can create update and delete own expense', function () {
    $owner = superAdmin();

    $this->actingAs($owner)
        ->post(route('admin.finance.expenses.store'), [
            'expense_date' => '2026-09-01',
            'category' => 'food',
            'description' => 'Lunch meeting',
            'amount' => 850,
            'payment_status' => 'paid',
            'notes' => 'Client lunch',
        ])
        ->assertRedirect(route('admin.finance.expenses.index'));

    $expense = FinanceExpense::query()->where('user_id', $owner->id)->firstOrFail();

    $this->actingAs($owner)
        ->put(route('admin.finance.expenses.update', $expense), [
            'expense_date' => '2026-09-02',
            'category' => 'travel',
            'description' => 'Cab fare',
            'amount' => 400,
            'payment_status' => 'pending',
            'notes' => null,
        ])
        ->assertRedirect(route('admin.finance.expenses.index'));

    expect($expense->fresh()->category)->toBe(FinanceExpenseCategory::Travel)
        ->and($expense->fresh()->payment_status)->toBe(FinanceExpensePaymentStatus::Pending);

    $this->actingAs($owner)
        ->delete(route('admin.finance.expenses.destroy', $expense))
        ->assertRedirect(route('admin.finance.expenses.index'));

    $this->assertDatabaseMissing('fin_expenses', ['id' => $expense->id]);
});

test('super admin cannot mutate another users expense', function () {
    $owner = superAdmin();
    $intruder = superAdmin();
    $expense = makeExpense($owner, ['description' => 'Secret expense']);

    $this->actingAs($intruder)
        ->get(route('admin.finance.expenses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('expenses.data', 0));

    $this->actingAs($intruder)
        ->put(route('admin.finance.expenses.update', $expense), [
            'expense_date' => '2026-09-03',
            'category' => 'other',
            'description' => 'Hacked',
            'amount' => 1,
            'payment_status' => 'paid',
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('admin.finance.expenses.destroy', $expense))
        ->assertForbidden();

    expect($expense->fresh()->description)->toBe('Secret expense');
});

test('expense summaries split paid and pending', function () {
    $owner = superAdmin();

    makeExpense($owner, ['amount' => 1000, 'payment_status' => FinanceExpensePaymentStatus::Paid]);
    makeExpense($owner, ['amount' => 500, 'payment_status' => FinanceExpensePaymentStatus::Pending, 'description' => 'Pending bill']);

    $this->actingAs($owner)
        ->get(route('admin.finance.expenses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summaries.total', 1500)
            ->where('summaries.paid', 1000)
            ->where('summaries.pending', 500));
});
