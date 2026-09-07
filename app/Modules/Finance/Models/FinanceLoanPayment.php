<?php

namespace App\Modules\Finance\Models;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceLoanPaymentType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $fin_loan_id
 * @property Carbon $payment_date
 * @property string $amount
 * @property FinanceLoanPaymentType $payment_type
 * @property string|null $remaining_balance_after
 * @property string|null $note
 */
class FinanceLoanPayment extends Model
{
    protected $table = 'fin_loan_payments';

    protected $fillable = [
        'user_id',
        'fin_loan_id',
        'payment_date',
        'amount',
        'payment_type',
        'remaining_balance_after',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'payment_type' => FinanceLoanPaymentType::class,
            'remaining_balance_after' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(FinanceLoan::class, 'fin_loan_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }
}
