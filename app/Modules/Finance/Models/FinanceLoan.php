<?php

namespace App\Modules\Finance\Models;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceLoanStatus;
use App\Modules\Finance\Support\FinanceLoanBalances;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $loan_date
 * @property string $lender_name
 * @property string|null $mobile_number
 * @property string $reason
 * @property string $loan_amount
 * @property string $amount_paid
 * @property string $remaining_amount
 * @property Carbon|null $due_date
 * @property FinanceLoanStatus $status
 * @property string|null $notes
 */
class FinanceLoan extends Model
{
    protected $table = 'fin_loans';

    protected $fillable = [
        'user_id',
        'loan_date',
        'lender_name',
        'mobile_number',
        'reason',
        'loan_amount',
        'amount_paid',
        'remaining_amount',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'due_date' => 'date',
            'loan_amount' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
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

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
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

        return [
            ...$data,
            'amount_paid' => $balances['amount_paid'],
            'remaining_amount' => $balances['remaining_amount'],
            'status' => $balances['status'],
        ];
    }

    public function applyPayment(float $amount): void
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
    }
}
