<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceLoanPaymentType;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Enums\FinanceLoanType;
use App\Modules\Finance\Http\Requests\FinanceLoanPaymentRequest;
use App\Modules\Finance\Http\Requests\FinanceLoanRequest;
use App\Modules\Finance\Models\FinanceLoan;
use App\Modules\Finance\Models\FinanceLoanPayment;
use App\Modules\Finance\Support\FinanceLoanPaymentRecorder;
use App\Support\Pagination;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinanceLoanController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', FinanceLoan::class);

        $filters = $this->listFilters($request);
        $user = $request->user();

        $loans = $this->filteredQuery($user, $filters)
            ->with(['payments' => fn ($query) => $query->orderByDesc('payment_date')->orderByDesc('id')])
            ->orderByDesc('loan_date')
            ->orderByDesc('id')
            ->paginate(Pagination::perPage($request, 15))
            ->withQueryString()
            ->through(fn (FinanceLoan $loan) => $this->summarise($loan));

        $summaryBase = $this->summaryQuery($user, $filters);

        return Inertia::render('Finance/loans/index', [
            'loans' => $loans,
            'filters' => $filters,
            'statuses' => FinanceLoanStatus::options(),
            'loan_types' => FinanceLoanType::options(),
            'payment_types' => FinanceLoanPaymentType::options(),
            'summaries' => [
                'count' => (int) (clone $summaryBase)
                    ->where('status', '!=', FinanceLoanStatus::Cancelled->value)
                    ->count(),
                'loan_amount' => (float) (clone $summaryBase)
                    ->where('status', '!=', FinanceLoanStatus::Cancelled->value)
                    ->sum('loan_amount'),
                'paid' => (float) (clone $summaryBase)
                    ->where('status', '!=', FinanceLoanStatus::Cancelled->value)
                    ->sum('amount_paid'),
                'remaining' => (float) (clone $summaryBase)
                    ->where('status', '!=', FinanceLoanStatus::Cancelled->value)
                    ->sum('remaining_amount'),
                'overdue' => (float) (clone $summaryBase)
                    ->where('status', FinanceLoanStatus::Overdue->value)
                    ->sum('remaining_amount'),
            ],
            'has_any_records' => FinanceLoan::query()->forUser($user)->exists(),
            'open_create' => $request->boolean('create'),
        ]);
    }

    public function store(FinanceLoanRequest $request): RedirectResponse
    {
        $this->authorize('create', FinanceLoan::class);

        FinanceLoan::query()->create([
            ...FinanceLoan::normalizedAttributes($request->validated()),
            'user_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.finance.loans.index')
            ->with('success', 'Loan recorded.');
    }

    public function update(FinanceLoanRequest $request, FinanceLoan $loan): RedirectResponse
    {
        $this->authorize('update', $loan);

        $loan->update(FinanceLoan::normalizedAttributes($request->validated()));

        return redirect()
            ->route('admin.finance.loans.index')
            ->with('success', 'Loan updated.');
    }

    public function destroy(Request $request, FinanceLoan $loan): RedirectResponse
    {
        $this->authorize('delete', $loan);

        $loan->delete();

        return redirect()
            ->route('admin.finance.loans.index')
            ->with('success', 'Loan deleted.');
    }

    public function recordPayment(FinanceLoanPaymentRequest $request, FinanceLoan $loan, FinanceLoanPaymentRecorder $recorder): RedirectResponse
    {
        $this->authorize('recordPayment', $loan);

        $recorder->record($request->user(), $loan, $request->validated());

        return redirect()
            ->route('admin.finance.loans.index')
            ->with('success', 'Repayment recorded. Linked expense created for the amount paid.');
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
                $filters['status'] !== '' && FinanceLoanStatus::tryFrom($filters['status']),
                fn (Builder $query) => $query->where('status', $filters['status']),
            );
    }

    /**
     * @param  array{search: string, status: string, date_from: string, date_to: string}  $filters
     */
    protected function summaryQuery(User $user, array $filters): Builder
    {
        return FinanceLoan::query()
            ->forUser($user)
            ->when($filters['search'] !== '', function (Builder $query) use ($filters) {
                $search = $filters['search'];
                $query->where(function (Builder $query) use ($search) {
                    $query->where('lender_name', 'like', "%{$search}%")
                        ->orWhere('mobile_number', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%");
                });
            })
            ->when($filters['date_from'] !== '', fn (Builder $query) => $query->whereDate('loan_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn (Builder $query) => $query->whereDate('loan_date', '<=', $filters['date_to']));
    }

    /**
     * @return array<string, mixed>
     */
    protected function summarise(FinanceLoan $loan): array
    {
        return [
            'id' => $loan->id,
            'loan_date' => $loan->loan_date->toDateString(),
            'loan_type' => $loan->loan_type instanceof FinanceLoanType
                ? $loan->loan_type->value
                : (string) ($loan->loan_type ?? FinanceLoanType::Personal->value),
            'lender_name' => $loan->lender_name,
            'mobile_number' => $loan->mobile_number,
            'reason' => $loan->reason,
            'loan_amount' => (float) $loan->loan_amount,
            'amount_paid' => (float) $loan->amount_paid,
            'remaining_amount' => (float) $loan->remaining_amount,
            'emi_amount' => $loan->emi_amount !== null ? (float) $loan->emi_amount : null,
            'emi_due_day' => $loan->emi_due_day,
            'next_emi_due_date' => $loan->next_emi_due_date?->toDateString(),
            'has_emi' => $loan->hasEmiSchedule(),
            'due_date' => $loan->due_date?->toDateString(),
            'status' => $loan->status instanceof FinanceLoanStatus
                ? $loan->status->value
                : (string) $loan->status,
            'notes' => $loan->notes,
            'payments' => $loan->relationLoaded('payments')
                ? $loan->payments->map(fn (FinanceLoanPayment $payment) => [
                    'id' => $payment->id,
                    'payment_date' => $payment->payment_date->toDateString(),
                    'payment_type' => $payment->payment_type instanceof FinanceLoanPaymentType
                        ? $payment->payment_type->value
                        : (string) ($payment->payment_type ?? FinanceLoanPaymentType::Partial->value),
                    'payment_type_label' => $payment->payment_type instanceof FinanceLoanPaymentType
                        ? $payment->payment_type->label()
                        : FinanceLoanPaymentType::Partial->label(),
                    'amount' => (float) $payment->amount,
                    'note' => $payment->note,
                    'remaining_balance_after' => $payment->remaining_balance_after !== null
                        ? (float) $payment->remaining_balance_after
                        : null,
                ])->values()->all()
                : [],
        ];
    }
}
