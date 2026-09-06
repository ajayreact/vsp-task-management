<?php

namespace App\Modules\Finance\Models;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceExpenseCategory;
use App\Modules\Finance\Enums\FinanceExpensePaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property Carbon $expense_date
 * @property FinanceExpenseCategory $category
 * @property string $description
 * @property string $amount
 * @property FinanceExpensePaymentStatus $payment_status
 * @property string|null $notes
 */
class FinanceExpense extends Model
{
    protected $table = 'fin_expenses';

    protected $fillable = [
        'user_id',
        'expense_date',
        'category',
        'description',
        'amount',
        'payment_status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'category' => FinanceExpenseCategory::class,
            'payment_status' => FinanceExpensePaymentStatus::class,
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
