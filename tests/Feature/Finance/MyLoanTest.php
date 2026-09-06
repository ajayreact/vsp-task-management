<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Models\FinanceLoan;
use App\Modules\Finance\Models\FinanceLoanPayment;

beforeEach(function () {
    $this->withoutVite();
});

function makeLoan(\App\Modules\Core\Models\User $user, array $overrides = []): FinanceLoan
{
    $loanAmount = (float) ($overrides['loan_amount'] ?? 50000);
    $amountPaid = (float) ($overrides['amount_paid'] ?? 0);
    $status = $overrides['status'] ?? FinanceLoanStatus::Active;

    unset($overrides['loan_amount'], $overrides['amount_paid'], $overrides['status'], $overrides['remaining_amount']);

    return FinanceLoan::query()->create(array_merge(
        FinanceLoan::normalizedAttributes([
            'loan_date' => now()->toDateString(),
            'lender_name' => 'Friend Lender',
            'mobile_number' => '9000011111',
            'reason' => 'Personal loan',
            'loan_amount' => $loanAmount,
            'amount_paid' => $amountPaid,
            'due_date' => now()->addMonth()->toDateString(),
            'status' => $status instanceof FinanceLoanStatus ? $status->value : $status,
            'notes' => null,
        ]),
        ['user_id' => $user->id],
        $overrides,
    ));
}

test('super admin can open my loans page', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.finance.loans.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/loans/index')
            ->where('summaries.count', 0)
            ->where('has_any_records', false)
            ->has('statuses', 5));
});

test('staff cannot open my loans', function () {
    $this->actingAs(staffWith(Ability::ViewEmployees))
        ->get(route('admin.finance.loans.index'))
        ->assertForbidden();
});

test('loan remaining is calculated and never negative', function () {
    $owner = superAdmin();

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.store'), [
            'loan_date' => '2026-09-01',
            'lender_name' => 'Bank',
            'reason' => 'Emergency',
            'loan_amount' => 10000,
            'amount_paid' => 12000,
            'status' => 'active',
            'due_date' => '2026-10-01',
        ])
        ->assertRedirect(route('admin.finance.loans.index'));

    $loan = FinanceLoan::query()->where('user_id', $owner->id)->firstOrFail();

    expect((float) $loan->amount_paid)->toBe(10000.0)
        ->and((float) $loan->remaining_amount)->toBe(0.0)
        ->and($loan->status)->toBe(FinanceLoanStatus::Paid);
});

test('record payment updates balances and status', function () {
    $owner = superAdmin();
    $loan = makeLoan($owner, [
        'loan_amount' => 50000,
        'amount_paid' => 20000,
        'status' => FinanceLoanStatus::PartiallyPaid,
    ]);

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-05',
            'amount' => 30000,
            'note' => 'Final payment',
        ])
        ->assertRedirect(route('admin.finance.loans.index'));

    $loan->refresh();

    expect((float) $loan->amount_paid)->toBe(50000.0)
        ->and((float) $loan->remaining_amount)->toBe(0.0)
        ->and($loan->status)->toBe(FinanceLoanStatus::Paid);

    $this->assertDatabaseHas('fin_loan_payments', [
        'fin_loan_id' => $loan->id,
        'user_id' => $owner->id,
        'amount' => '30000.00',
    ]);
});

test('payment cannot exceed remaining balance', function () {
    $owner = superAdmin();
    $loan = makeLoan($owner, [
        'loan_amount' => 10000,
        'amount_paid' => 7000,
        'status' => FinanceLoanStatus::PartiallyPaid,
    ]);

    $this->actingAs($owner)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-05',
            'amount' => 5000,
        ])
        ->assertSessionHasErrors(['amount']);

    expect(FinanceLoanPayment::query()->count())->toBe(0)
        ->and((float) $loan->fresh()->amount_paid)->toBe(7000.0);
});

test('super admin cannot access another users loan or payment', function () {
    $owner = superAdmin();
    $intruder = superAdmin();
    $loan = makeLoan($owner, ['lender_name' => 'Secret Lender']);

    $this->actingAs($intruder)
        ->get(route('admin.finance.loans.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('loans.data', 0));

    $this->actingAs($intruder)
        ->post(route('admin.finance.loans.payments.store', $loan), [
            'payment_date' => '2026-09-05',
            'amount' => 100,
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('admin.finance.loans.destroy', $loan))
        ->assertForbidden();

    expect($loan->fresh()->lender_name)->toBe('Secret Lender');
});

test('loan summaries exclude cancelled loans', function () {
    $owner = superAdmin();

    makeLoan($owner, ['loan_amount' => 10000, 'amount_paid' => 2000, 'status' => FinanceLoanStatus::PartiallyPaid]);
    makeLoan($owner, [
        'loan_amount' => 5000,
        'amount_paid' => 0,
        'status' => FinanceLoanStatus::Cancelled,
        'lender_name' => 'Cancelled Lender',
    ]);

    $this->actingAs($owner)
        ->get(route('admin.finance.loans.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summaries.count', 1)
            ->where('summaries.loan_amount', 10000)
            ->where('summaries.paid', 2000)
            ->where('summaries.remaining', 8000));
});
