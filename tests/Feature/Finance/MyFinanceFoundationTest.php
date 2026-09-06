<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceIncome;
use App\Modules\Finance\Models\FinanceLoan;

beforeEach(function () {
    $this->withoutVite();
});

test('super admin can open my finance hub', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.finance.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/index')
            ->where('counts.income', 0)
            ->where('counts.expenses', 0)
            ->where('counts.loans', 0)
            ->has('summaries')
            ->has('period'));
});

test('staff without super admin role cannot open my finance', function () {
    $this->actingAs(staffWith(Ability::ViewEmployees))
        ->get(route('admin.finance.index'))
        ->assertForbidden();
});

test('employee with task access cannot open my finance', function () {
    $this->actingAs(employeeWith(Ability::AccessTasks)->user)
        ->get(route('admin.finance.index'))
        ->assertForbidden();
});

test('guests are redirected from my finance', function () {
    $this->get(route('admin.finance.index'))
        ->assertRedirect(route('login'));
});

test('finance counts are scoped to the authenticated super admin only', function () {
    $owner = superAdmin();
    $other = superAdmin();

    FinanceIncome::query()->create([
        'user_id' => $other->id,
        'income_date' => now()->toDateString(),
        'person_name' => 'Other Person',
        'reason' => 'Other income',
        'amount' => 1000,
        'status' => 'received',
    ]);

    FinanceExpense::query()->create([
        'user_id' => $other->id,
        'expense_date' => now()->toDateString(),
        'category' => 'personal',
        'description' => 'Other expense',
        'amount' => 500,
        'payment_status' => 'paid',
    ]);

    FinanceLoan::query()->create(array_merge(
        FinanceLoan::normalizedAttributes([
            'loan_date' => now()->toDateString(),
            'lender_name' => 'Other Lender',
            'reason' => 'Other loan',
            'loan_amount' => 5000,
            'amount_paid' => 0,
            'due_date' => null,
            'status' => 'active',
        ]),
        ['user_id' => $other->id],
    ));

    FinanceIncome::query()->create([
        'user_id' => $owner->id,
        'income_date' => now()->toDateString(),
        'person_name' => 'Owner Person',
        'reason' => 'Owner income',
        'amount' => 2000,
        'status' => 'pending',
    ]);

    $this->actingAs($owner)
        ->get(route('admin.finance.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/index')
            ->where('counts.income', 1)
            ->where('counts.expenses', 0)
            ->where('counts.loans', 0));
});

test('finance income policy denies cross-user access', function () {
    $owner = superAdmin();
    $intruder = superAdmin();

    $income = FinanceIncome::query()->create([
        'user_id' => $owner->id,
        'income_date' => now()->toDateString(),
        'person_name' => 'Owner',
        'reason' => 'Private',
        'amount' => 100,
        'status' => 'received',
    ]);

    expect($intruder->can('view', $income))->toBeFalse()
        ->and($owner->can('view', $income))->toBeTrue()
        ->and(staffWith()->can('view', $income))->toBeFalse();
});
