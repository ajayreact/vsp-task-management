<?php

namespace App\Modules\Finance\Models;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Enums\FinanceLoanType;
use App\Modules\Finance\Support\FinanceLoanBalances;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property FinanceLoanType $loan_type
 * @property Carbon $loan_date
 * @property string $lender_name
 * @property string|null $mobile_number
 * @property string $reason
 * @property string $loan_amount
 * @property string $amount_paid
 * @property string $remaining_amount
 * @property string|null $emi_amount
 * @property int|null $emi_due_day
 * @property Carbon|null $next_emi_due_date
 * @property Carbon|null $due_date
 * @property FinanceLoanStatus $status
 * @property string|null $notes
 */
class FinanceLoan extends Model
{
    protected $table = 'fin_loans';

    protected $fillable = [
        'user_id',
        'loan_type',
        'loan_date',
        'lender_name',
        'mobile_number',
        'reason',
        'loan_amount',
        'amount_paid',
        'remaining_amount',
        'emi_amount',
        'emi_due_day',
        'next_emi_due_date',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'loan_type' => FinanceLoanType::class,
            'loan_date' => 'date',
            'due_date' => 'date',
            'next_emi_due_date' => 'date',
            'loan_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'emi_amount' => 'decimal:2',
            'emi_due_day' => 'integer',
            'status' => FinanceLoanStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FinanceLoanPayment::class, 'fin_loan_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(FinanceExpense::class, 'fin_loan_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function hasEmiSchedule(): bool
    {
        return $this->emi_amount !== null
            && (float) $this->emi_amount > 0
            && $this->emi_due_day !== null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizedAttributes(array $data): array
    {
        $requested = FinanceLoanStatus::from((string) $data['status']);
        $balances = FinanceLoanBalances::resolve(
            (float) $data['loan_amount'],
            (float) ($data['amount_paid'] ?? 0),
            $requested,
            $data['due_date'] ?? null,
        );

        $emiAmount = array_key_exists('emi_amount', $data) && $data['emi_amount'] !== null && $data['emi_amount'] !== ''
            ? (float) $data['emi_amount']
            : null;
        $emiDueDay = array_key_exists('emi_due_day', $data) && $data['emi_due_day'] !== null && $data['emi_due_day'] !== ''
            ? (int) $data['emi_due_day']
            : null;

        if ($emiAmount !== null && $emiAmount <= 0) {
            $emiAmount = null;
            $emiDueDay = null;
        }

        if ($emiDueDay !== null && ($emiDueDay < 1 || $emiDueDay > 28)) {
            $emiDueDay = null;
        }

        $nextEmi = null;
        if ($emiAmount !== null && $emiDueDay !== null && $balances['remaining_amount'] > 0) {
            $nextEmi = self::computeNextEmiDueDate($emiDueDay, isset($data['next_emi_due_date']) ? (string) $data['next_emi_due_date'] : null);
        }

        return [
            ...$data,
            'loan_type' => $data['loan_type'] ?? FinanceLoanType::Personal->value,
            'amount_paid' => $balances['amount_paid'],
            'remaining_amount' => $balances['remaining_amount'],
            'status' => $balances['status'],
            'emi_amount' => $emiAmount,
            'emi_due_day' => $emiDueDay,
            'next_emi_due_date' => $nextEmi,
        ];
    }

    public function applyPayment(float $amount, bool $advanceEmiSchedule = true): void
    {
        $balances = FinanceLoanBalances::resolve(
            (float) $this->loan_amount,
            (float) $this->amount_paid + $amount,
            FinanceLoanStatus::Active,
            $this->due_date,
        );

        $this->amount_paid = $balances['amount_paid'];
        $this->remaining_amount = $balances['remaining_amount'];
        $this->status = $balances['status'];

        if ((float) $this->remaining_amount <= 0) {
            $this->next_emi_due_date = null;

            return;
        }

        if ($advanceEmiSchedule && $this->hasEmiSchedule()) {
            $this->next_emi_due_date = self::computeNextEmiDueDate((int) $this->emi_due_day, null, after: now());
        }
    }

    public static function computeNextEmiDueDate(int $dueDay, ?string $preferred = null, ?Carbon $after = null): string
    {
        $after ??= now();
        $dueDay = max(1, min(28, $dueDay));

        if ($preferred) {
            try {
                $preferredDate = Carbon::parse($preferred)->startOfDay();
                if ($preferredDate->gte($after->copy()->startOfDay())) {
                    return $preferredDate->toDateString();
                }
            } catch (\Throwable) {
                // fall through
            }
        }

        $candidate = $after->copy()->day($dueDay)->startOfDay();
        if ($candidate->lt($after->copy()->startOfDay())) {
            $candidate->addMonthNoOverflow()->day($dueDay);
        }

        return $candidate->toDateString();
    }
}
