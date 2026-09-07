<?php

use App\Modules\Finance\Enums\FinanceExpenseCategory;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Enums\FinanceLoanPaymentType;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Enums\FinanceLoanType;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceLoan;
use App\Modules\Finance\Models\FinanceLoanPayment;

beforeEach(function () {
    $this->withoutVite();
});

function makeRepaymentLoan(\App\Modules\Core\Models\User $user, array $overrides = []): FinanceLoan
{
    $attributes = array_merge([
        'loan_type' => FinanceLoanType::Emi->value,
        'loan_date' => '2026-01-01',
        'lender_name' => 'Bank Loan',
        'reason' => 'Personal',
        'loan_amount' => 109000,
        'amount_paid' => 0,
        'emi_amount' => 23000,
        'emi_due_day' => 5,
        'status' => FinanceLoanStatus::Active->value,
    ], $overrides);

    return FinanceLoan::query()->create(array_merge(
        FinanceLoan::normalizedAttributes($attributes),
        ['user_id' => $user->id],
    ));
}

test('emi repayment creates one linked expense for the actual amount', function () {
    $owner = superAdmin();
    $loan = makeRepaymentLoan($owner);

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-05',
            'amount' => 23000,
            'payment_type' => 'emi',
            'note' => 'September EMI',
        ])
        ->assertRedirect();

    $loan->refresh();
    $payment = FinanceLoanPayment::query()->where('fin_loan_id', $loan->id)->firstOrFail();
    $expense = FinanceExpense::query()->where('fin_loan_payment_id', $payment->id)->firstOrFail();

    expect((float) $loan->remaining_amount)->toBe(86000.0)
        ->and((float) $loan->amount_paid)->toBe(23000.0)
        ->and($payment->payment_type)->toBe(FinanceLoanPaymentType::Emi)
        ->and((float) $payment->remaining_balance_after)->toBe(86000.0)
        ->and((float) $expense->amount)->toBe(23000.0)
        ->and($expense->category)->toBe(FinanceExpenseCategory::LoanPayment)
        ->and($expense->payment_status)->toBe(FinanceExpensePaymentStatus::Paid)
        ->and(FinanceExpense::query()->count())->toBe(1);
});

test('partial repayment of 5000 reduces outstanding and creates 5000 expense', function () {
    $owner = superAdmin();
    $loan = makeRepaymentLoan($owner);

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-10',
            'amount' => 5000,
            'payment_type' => 'partial',
            'note' => 'Extra cash',
        ])
        ->assertRedirect();

    $loan->refresh();
    $expense = FinanceExpense::query()->firstOrFail();

    expect((float) $loan->remaining_amount)->toBe(104000.0)
        ->and((float) $loan->amount_paid)->toBe(5000.0)
        ->and((float) $expense->amount)->toBe(5000.0)
        ->and($expense->fin_loan_id)->toBe($loan->id);
});

test('partial repayment of 10000 does not count scheduled emi as paid expense', function () {
    $owner = superAdmin();
    $loan = makeRepaymentLoan($owner);

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-20',
            'amount' => 10000,
            'payment_type' => 'partial',
            'note' => 'Extra payment',
        ])
        ->assertRedirect();

    $this->actingAs($owner)
        ->get(route('admin.finance.index', ['period' => 'month', 'month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summaries.paid_expenses', 10000)
            ->where('summaries.emi_due', 23000)
            ->where('summaries.loan_outstanding', 99000)
            ->where('summaries.total_monthly_commitments', 23000));
});

test('repayment larger than emi is allowed as one payment', function () {
    $owner = superAdmin();
    $loan = makeRepaymentLoan($owner);

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-05',
            'amount' => 30000,
            'payment_type' => 'partial',
            'note' => 'EMI plus extra',
        ])
        ->assertRedirect();

    $loan->refresh();
    $payments = FinanceLoanPayment::query()->where('fin_loan_id', $loan->id)->get();
    $expenses = FinanceExpense::query()->where('fin_loan_id', $loan->id)->get();

    expect($payments)->toHaveCount(1)
        ->and($expenses)->toHaveCount(1)
        ->and((float) $payments->first()->amount)->toBe(30000.0)
        ->and((float) $expenses->first()->amount)->toBe(30000.0)
        ->and((float) $loan->remaining_amount)->toBe(79000.0);
});

test('full repayment clears outstanding and marks loan paid', function () {
    $owner = superAdmin();
    $loan = makeRepaymentLoan($owner, [
        'loan_amount' => 50000,
        'amount_paid' => 20000,
    ]);

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-05',
            'amount' => 30000,
            'payment_type' => 'full',
        ])
        ->assertRedirect();

    $loan->refresh();

    expect((float) $loan->remaining_amount)->toBe(0.0)
        ->and((float) $loan->amount_paid)->toBe(50000.0)
        ->and($loan->status)->toBe(FinanceLoanStatus::Paid)
        ->and(FinanceLoanPayment::query()->first()->payment_type)->toBe(FinanceLoanPaymentType::Full);
});

test('repayment cannot exceed outstanding balance', function () {
    $owner = superAdmin();
    $loan = makeRepaymentLoan($owner, [
        'loan_amount' => 10000,
        'amount_paid' => 7000,
        'emi_amount' => null,
        'emi_due_day' => null,
    ]);

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-05',
            'amount' => 5000,
            'payment_type' => 'partial',
        ])
        ->assertSessionHasErrors(['amount']);

    expect(FinanceLoanPayment::query()->count())->toBe(0)
        ->and((float) $loan->fresh()->remaining_amount)->toBe(3000.0);
});

test('gold loan without emi supports flexible partial repayment', function () {
    $owner = superAdmin();

    $loan = FinanceLoan::query()->create(array_merge(
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

    expect($loan->hasEmiSchedule())->toBeFalse();

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-12',
            'amount' => 25000,
            'payment_type' => 'partial',
            'note' => 'Partial redeem',
        ])
        ->assertRedirect();

    $loan->refresh();
    $expense = FinanceExpense::query()->firstOrFail();

    expect((float) $loan->remaining_amount)->toBe(125000.0)
        ->and((float) $expense->amount)->toBe(25000.0)
        ->and($expense->fin_loan_payment_id)->not->toBeNull();

    $this->actingAs($owner)
        ->get(route('admin.finance.index', ['period' => 'month', 'month' => '2026-09']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summaries.paid_expenses', 25000)
            ->where('summaries.emi_due', 0)
            ->where('summaries.loan_outstanding', 125000));
});

test('full repayment type requires amount equal to outstanding', function () {
    $owner = superAdmin();
    $loan = makeRepaymentLoan($owner);

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-05',
            'amount' => 10000,
            'payment_type' => 'full',
        ])
        ->assertSessionHasErrors(['amount']);
});
