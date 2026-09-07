<?php

use App\Modules\Finance\Enums\FinanceExpenseCategory;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Enums\FinanceIncomeStatus;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Enums\FinanceLoanType;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceIncome;
use App\Modules\Finance\Models\FinanceLoan;
use App\Modules\Finance\Models\FinanceLoanPayment;
use App\Modules\Finance\Models\FinanceRecurringExpense;

beforeEach(function () {
    $this->withoutVite();
});

test('loan payment creates linked paid expense in one transaction', function () {
    $owner = superAdmin();

    $loan = FinanceLoan::query()->create(array_merge(
        FinanceLoan::normalizedAttributes([
            'loan_type' => FinanceLoanType::Emi->value,
            'loan_date' => '2026-01-01',
            'lender_name' => 'Personal EMI Loan',
            'reason' => 'Car',
            'loan_amount' => 109000,
            'amount_paid' => 0,
            'emi_amount' => 23000,
            'emi_due_day' => 5,
            'status' => FinanceLoanStatus::Active->value,
        ]),
        ['user_id' => $owner->id],
    ));

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-05',
            'amount' => 23000,
            'payment_type' => 'emi',
            'note' => 'September EMI',
        ])
        ->assertRedirect(route('admin.finance.loans.index'));

    $loan->refresh();

    expect((float) $loan->remaining_amount)->toBe(86000.0)
        ->and((float) $loan->amount_paid)->toBe(23000.0);

    $payment = FinanceLoanPayment::query()->where('fin_loan_id', $loan->id)->firstOrFail();
    $expense = FinanceExpense::query()->where('fin_loan_payment_id', $payment->id)->firstOrFail();

    expect((float) $expense->amount)->toBe(23000.0)
        ->and($expense->category)->toBe(FinanceExpenseCategory::LoanPayment)
        ->and($expense->payment_status)->toBe(FinanceExpensePaymentStatus::Paid)
        ->and($expense->fin_loan_id)->toBe($loan->id)
        ->and($expense->excluded_as_liability)->toBeFalse();
});

test('monthly commitments exclude loan outstanding and double count correctly', function () {
    $owner = superAdmin();

    FinanceIncome::query()->create([
        'user_id' => $owner->id,
        'income_date' => '2026-09-01',
        'person_name' => 'Client',
        'reason' => 'Fee',
        'amount' => 50000,
        'status' => FinanceIncomeStatus::Received,
    ]);

    FinanceExpense::query()->create([
        'user_id' => $owner->id,
        'expense_date' => '2026-09-02',
        'category' => FinanceExpenseCategory::Food,
        'description' => 'Food',
        'amount' => 3000,
        'payment_status' => FinanceExpensePaymentStatus::Paid,
        'excluded_as_liability' => false,
    ]);

    FinanceLoan::query()->create(array_merge(
        FinanceLoan::normalizedAttributes([
            'loan_type' => FinanceLoanType::Emi->value,
            'loan_date' => '2026-01-01',
            'lender_name' => 'EMI Loan',
            'reason' => 'Loan',
            'loan_amount' => 109000,
            'amount_paid' => 0,
            'emi_amount' => 23000,
            'emi_due_day' => 5,
            'status' => FinanceLoanStatus::Active->value,
        ]),
        ['user_id' => $owner->id],
    ));

    FinanceLoan::query()->create(array_merge(
        FinanceLoan::normalizedAttributes([
            'loan_type' => FinanceLoanType::Gold->value,
            'loan_date' => '2026-01-01',
            'lender_name' => 'Union Bank Gold Loan',
            'reason' => 'Gold',
            'loan_amount' => 150000,
            'amount_paid' => 0,
            'emi_amount' => null,
            'emi_due_day' => null,
            'status' => FinanceLoanStatus::Active->value,
        ]),
        ['user_id' => $owner->id],
    ));

    FinanceRecurringExpense::query()->create([
        'user_id' => $owner->id,
        'title' => 'Room Rent',
        'amount' => 6500,
        'category' => FinanceExpenseCategory::Personal,
        'due_day' => 1,
        'active' => true,
        'start_date' => '2026-01-01',
        'end_date' => null,
        'notes' => null,
    ]);

    $this->actingAs($owner)
        ->get(route('admin.finance.index', ['period' => 'month', 'month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/index')
            ->where('summaries.received_income', 50000)
            ->where('summaries.paid_expenses', 3000)
            ->where('summaries.net_balance', 47000)
            ->where('summaries.emi_due', 23000)
            ->where('summaries.recurring_due', 6500)
            ->where('summaries.total_monthly_commitments', 29500)
            ->where('summaries.loan_outstanding', 259000)
            ->where('period.label', 'September 2026'));
});

test('recording emi payment clears emi due and counts only emi as paid expense', function () {
    $owner = superAdmin();

    $loan = FinanceLoan::query()->create(array_merge(
        FinanceLoan::normalizedAttributes([
            'loan_type' => FinanceLoanType::Emi->value,
            'loan_date' => '2026-01-01',
            'lender_name' => 'EMI Loan',
            'reason' => 'Loan',
            'loan_amount' => 109000,
            'amount_paid' => 0,
            'emi_amount' => 23000,
            'emi_due_day' => 5,
            'status' => FinanceLoanStatus::Active->value,
        ]),
        ['user_id' => $owner->id],
    ));

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-05',
            'amount' => 23000,
            'payment_type' => 'emi',
        ])
        ->assertRedirect();

    $this->actingAs($owner)
        ->get(route('admin.finance.index', ['period' => 'month', 'month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summaries.emi_due', 0)
            ->where('summaries.paid_expenses', 23000)
            ->where('summaries.loan_outstanding', 86000)
            ->where('summaries.total_monthly_commitments', 0));
});

test('paid recurring expense clears recurring due for the month', function () {
    $owner = superAdmin();

    $template = FinanceRecurringExpense::query()->create([
        'user_id' => $owner->id,
        'title' => 'Room Rent',
        'amount' => 6500,
        'category' => FinanceExpenseCategory::Personal,
        'due_day' => 1,
        'active' => true,
        'start_date' => '2026-01-01',
        'end_date' => null,
        'notes' => null,
    ]);

    $this->actingAs($owner)
        ->get(route('admin.finance.index', ['period' => 'month', 'month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('summaries.recurring_due', 6500));

    FinanceExpense::query()->create([
        'user_id' => $owner->id,
        'recurring_template_id' => $template->id,
        'expense_date' => '2026-09-01',
        'category' => FinanceExpenseCategory::Personal,
        'description' => 'Room Rent',
        'amount' => 6500,
        'payment_status' => FinanceExpensePaymentStatus::Paid,
        'excluded_as_liability' => false,
    ]);

    $this->actingAs($owner)
        ->get(route('admin.finance.index', ['period' => 'month', 'month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summaries.recurring_due', 0)
            ->where('summaries.paid_expenses', 6500));
});

test('convert expense to loan excludes it from expense totals and is reversible', function () {
    $owner = superAdmin();

    $expense = FinanceExpense::query()->create([
        'user_id' => $owner->id,
        'expense_date' => '2026-09-01',
        'category' => FinanceExpenseCategory::Other,
        'description' => 'Gold Loan Union Bank',
        'amount' => 150000,
        'payment_status' => FinanceExpensePaymentStatus::Pending,
        'excluded_as_liability' => false,
    ]);

    $this->actingAs($owner)
        ->post(route('admin.finance.expenses.convert-to-loan', $expense), [
            'confirm' => 1,
            'loan_type' => 'gold',
            'lender_name' => 'Gold Loan Union Bank',
            'reason' => 'Gold Loan Union Bank',
        ])
        ->assertRedirect(route('admin.finance.loans.index'));

    $expense->refresh();

    expect($expense->excluded_as_liability)->toBeTrue()
        ->and($expense->converted_to_fin_loan_id)->not->toBeNull();

    $loan = FinanceLoan::query()->findOrFail($expense->converted_to_fin_loan_id);

    expect((float) $loan->remaining_amount)->toBe(150000.0)
        ->and($loan->loan_type)->toBe(FinanceLoanType::Gold);

    $this->actingAs($owner)
        ->get(route('admin.finance.expenses.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('summaries.total', 0));

    $this->actingAs($owner)
        ->post(route('admin.finance.expenses.revert-conversion', $expense))
        ->assertRedirect(route('admin.finance.expenses.index'));

    $expense->refresh();

    expect($expense->excluded_as_liability)->toBeFalse()
        ->and($expense->converted_to_fin_loan_id)->toBeNull()
        ->and(FinanceLoan::query()->find($loan->id))->toBeNull();
});

test('blocks loan-like expense descriptions without converting', function () {
    $owner = superAdmin();

    $this->actingAs($owner)
        ->post(route('admin.finance.expenses.store'), [
            'expense_date' => '2026-09-01',
            'category' => 'other',
            'description' => 'Loan Repayment',
            'amount' => 109000,
            'payment_status' => 'pending',
        ])
        ->assertSessionHasErrors(['amount']);

    expect(FinanceExpense::query()->count())->toBe(0);
});

test('gold loan without emi can be created and is not monthly expense', function () {
    $owner = superAdmin();

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.store'), [
            'loan_date' => '2026-09-01',
            'loan_type' => 'gold',
            'lender_name' => 'Union Bank Gold Loan',
            'reason' => 'Gold loan',
            'loan_amount' => 150000,
            'amount_paid' => 0,
            'status' => 'active',
        ])
        ->assertRedirect(route('admin.finance.loans.index'));

    $loan = FinanceLoan::query()->where('user_id', $owner->id)->firstOrFail();

    expect($loan->hasEmiSchedule())->toBeFalse()
        ->and((float) $loan->remaining_amount)->toBe(150000.0);

    $this->actingAs($owner)
        ->get(route('admin.finance.index', ['period' => 'month', 'month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summaries.emi_due', 0)
            ->where('summaries.paid_expenses', 0)
            ->where('summaries.loan_outstanding', 150000)
            ->where('summaries.total_monthly_commitments', 0));
});
