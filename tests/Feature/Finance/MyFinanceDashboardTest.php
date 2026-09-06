<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Enums\FinanceIncomeStatus;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceIncome;
use App\Modules\Finance\Models\FinanceLoan;

beforeEach(function () {
    $this->withoutVite();
});

test('dashboard shows period summaries and net balance', function () {
    $owner = superAdmin();

    FinanceIncome::query()->create([
        'user_id' => $owner->id,
        'income_date' => now()->toDateString(),
        'person_name' => 'Client',
        'reason' => 'Fee',
        'amount' => 10000,
        'status' => FinanceIncomeStatus::Received,
    ]);
    FinanceExpense::query()->create([
        'user_id' => $owner->id,
        'expense_date' => now()->toDateString(),
        'category' => 'food',
        'description' => 'Lunch',
        'amount' => 2500,
        'payment_status' => FinanceExpensePaymentStatus::Paid,
    ]);
    FinanceLoan::query()->create(array_merge(
        FinanceLoan::normalizedAttributes([
            'loan_date' => now()->toDateString(),
            'lender_name' => 'Lender',
            'reason' => 'Help',
            'loan_amount' => 5000,
            'amount_paid' => 1000,
            'due_date' => now()->addDays(3)->toDateString(),
            'status' => FinanceLoanStatus::PartiallyPaid->value,
        ]),
        ['user_id' => $owner->id],
    ));

    $this->actingAs($owner)
        ->get(route('admin.finance.index', ['period' => 'this_month']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/index')
            ->where('summaries.total_received', 10000)
            ->where('summaries.paid_expenses', 2500)
            ->where('summaries.net_balance', 7500)
            ->where('summaries.total_loans', 1)
            ->where('summaries.loan_remaining', 4000)
            ->has('recent_activity')
            ->has('loan_alerts')
            ->has('period_options', 5));
});

test('staff cannot open finance dashboard or export', function () {
    $staff = staffWith(Ability::ViewEmployees);

    $this->actingAs($staff)->get(route('admin.finance.index'))->assertForbidden();
    $this->actingAs($staff)->get(route('admin.finance.export.income'))->assertForbidden();
    $this->actingAs($staff)->get(route('admin.finance.export.expenses'))->assertForbidden();
    $this->actingAs($staff)->get(route('admin.finance.export.loans'))->assertForbidden();
});

test('super admin can export income expenses and loans', function () {
    $owner = superAdmin();

    FinanceIncome::query()->create([
        'user_id' => $owner->id,
        'income_date' => now()->toDateString(),
        'person_name' => 'Export Person',
        'reason' => 'Export income',
        'amount' => 100,
        'status' => FinanceIncomeStatus::Received,
    ]);

    $this->actingAs($owner)
        ->get(route('admin.finance.export.income', ['period' => 'all']))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($owner)
        ->get(route('admin.finance.export.expenses', ['period' => 'all']))
        ->assertOk();

    $this->actingAs($owner)
        ->get(route('admin.finance.export.loans', ['period' => 'all']))
        ->assertOk();
});
