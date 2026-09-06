<?php

namespace App\Modules\Finance\Models;

use App\Modules\Core\Models\User;
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
        'note',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
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
