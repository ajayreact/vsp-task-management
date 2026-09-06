<?php

use App\Modules\Core\Enums\Ability;
use App\Modules\Finance\Enums\FinanceIncomeStatus;
use App\Modules\Finance\Models\FinanceIncome;

beforeEach(function () {
    $this->withoutVite();
});

function makeIncome(\App\Modules\Core\Models\User $user, array $overrides = []): FinanceIncome
{
    return FinanceIncome::query()->create(array_merge([
        'user_id' => $user->id,
        'income_date' => now()->toDateString(),
        'person_name' => 'Ravi Kumar',
        'mobile_number' => '9876543210',
        'reason' => 'Consulting fee',
        'amount' => 15000.50,
        'status' => FinanceIncomeStatus::Received,
        'notes' => 'Paid via UPI',
    ], $overrides));
}

test('super admin can open my income page', function () {
    $this->actingAs(superAdmin())
        ->get(route('admin.finance.income.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Finance/income/index')
            ->where('summaries.total', 0)
            ->where('summaries.received', 0)
            ->where('summaries.pending', 0)
            ->where('has_any_records', false)
            ->has('statuses', 3));
});

test('staff without super admin role cannot open my income', function () {
    $this->actingAs(staffWith(Ability::ViewEmployees))
        ->get(route('admin.finance.income.index'))
        ->assertForbidden();
});

test('employee with task access cannot open my income', function () {
    $this->actingAs(employeeWith(Ability::AccessTasks)->user)
        ->get(route('admin.finance.income.index'))
        ->assertForbidden();
});

test('guests are redirected from my income', function () {
    $this->get(route('admin.finance.income.index'))
        ->assertRedirect(route('login'));
});

test('super admin can create income with validated fields', function () {
    $owner = superAdmin();

    $this->actingAs($owner)
        ->post(route('admin.finance.income.store'), [
            'income_date' => '2026-09-01',
            'person_name' => 'Anita Sharma',
            'mobile_number' => '9123456789',
            'reason' => 'Project payment',
            'amount' => 25000,
            'status' => 'received',
            'notes' => 'September installment',
        ])
        ->assertRedirect(route('admin.finance.income.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('fin_incomes', [
        'user_id' => $owner->id,
        'person_name' => 'Anita Sharma',
        'reason' => 'Project payment',
        'amount' => '25000.00',
        'status' => 'received',
    ]);
});

test('income create requires person name reason amount date and status', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.finance.income.store'), [
            'mobile_number' => '9000000000',
            'notes' => 'Only optional fields',
        ])
        ->assertSessionHasErrors(['income_date', 'person_name', 'reason', 'amount', 'status']);
});

test('income amount must be greater than zero', function () {
    $this->actingAs(superAdmin())
        ->post(route('admin.finance.income.store'), [
            'income_date' => '2026-09-01',
            'person_name' => 'Test',
            'reason' => 'Invalid amount',
            'amount' => 0,
            'status' => 'pending',
        ])
        ->assertSessionHasErrors(['amount']);
});

test('super admin can update and delete own income', function () {
    $owner = superAdmin();
    $income = makeIncome($owner, ['status' => FinanceIncomeStatus::Pending]);

    $this->actingAs($owner)
        ->put(route('admin.finance.income.update', $income), [
            'income_date' => '2026-09-02',
            'person_name' => 'Updated Name',
            'mobile_number' => null,
            'reason' => 'Updated reason',
            'amount' => 999.99,
            'status' => 'received',
            'notes' => null,
        ])
        ->assertRedirect(route('admin.finance.income.index'));

    expect($income->fresh()->person_name)->toBe('Updated Name')
        ->and($income->fresh()->status)->toBe(FinanceIncomeStatus::Received);

    $this->actingAs($owner)
        ->delete(route('admin.finance.income.destroy', $income))
        ->assertRedirect(route('admin.finance.income.index'));

    $this->assertDatabaseMissing('fin_incomes', ['id' => $income->id]);
});

test('super admin cannot view update or delete another users income', function () {
    $owner = superAdmin();
    $intruder = superAdmin();
    $income = makeIncome($owner, ['person_name' => 'Secret Owner']);

    $this->actingAs($intruder)
        ->get(route('admin.finance.income.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('has_any_records', false)
            ->has('incomes.data', 0));

    $this->actingAs($intruder)
        ->put(route('admin.finance.income.update', $income), [
            'income_date' => '2026-09-03',
            'person_name' => 'Hacked',
            'reason' => 'Should fail',
            'amount' => 1,
            'status' => 'received',
        ])
        ->assertForbidden();

    $this->actingAs($intruder)
        ->delete(route('admin.finance.income.destroy', $income))
        ->assertForbidden();

    expect($income->fresh()->person_name)->toBe('Secret Owner');
});

test('income summaries exclude cancelled and scope to owner', function () {
    $owner = superAdmin();
    $other = superAdmin();

    makeIncome($owner, [
        'amount' => 1000,
        'status' => FinanceIncomeStatus::Received,
    ]);
    makeIncome($owner, [
        'amount' => 500,
        'status' => FinanceIncomeStatus::Pending,
        'person_name' => 'Pending Person',
    ]);
    makeIncome($owner, [
        'amount' => 2000,
        'status' => FinanceIncomeStatus::Cancelled,
        'person_name' => 'Cancelled Person',
    ]);
    makeIncome($other, [
        'amount' => 9999,
        'status' => FinanceIncomeStatus::Received,
        'person_name' => 'Other Owner',
    ]);

    $this->actingAs($owner)
        ->get(route('admin.finance.income.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('summaries.total', 1500)
            ->where('summaries.received', 1000)
            ->where('summaries.pending', 500)
            ->where('has_any_records', true)
            ->has('incomes.data', 3));
});

test('income search and status filters work', function () {
    $owner = superAdmin();

    makeIncome($owner, [
        'person_name' => 'Alpha Client',
        'status' => FinanceIncomeStatus::Received,
        'reason' => 'Retainership',
    ]);
    makeIncome($owner, [
        'person_name' => 'Beta Vendor',
        'status' => FinanceIncomeStatus::Pending,
        'reason' => 'Invoice 42',
    ]);

    $this->actingAs($owner)
        ->get(route('admin.finance.income.index', ['search' => 'Beta']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('incomes.data', 1)
            ->where('incomes.data.0.person_name', 'Beta Vendor'));

    $this->actingAs($owner)
        ->get(route('admin.finance.income.index', ['status' => 'pending']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('incomes.data', 1)
            ->where('incomes.data.0.status', 'pending'));
});
