<?php

namespace App\Modules\Finance\Models;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceIncomeStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $income_date
 * @property string $person_name
 * @property string|null $mobile_number
 * @property string $reason
 * @property string $amount
 * @property FinanceIncomeStatus $status
 * @property string|null $notes
 */
class FinanceIncome extends Model
{
    protected $table = 'fin_incomes';

    protected $fillable = [
        'user_id',
        'income_date',
        'person_name',
        'mobile_number',
        'reason',
        'amount',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'income_date' => 'date',
            'amount' => 'decimal:2',
            'status' => FinanceIncomeStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
