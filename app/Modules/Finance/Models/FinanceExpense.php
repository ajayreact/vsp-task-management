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
 * @property int|null $fin_loan_id
 * @property int|null $fin_loan_payment_id
 * @property int|null $recurring_template_id
 * @property Carbon $expense_date
 * @property FinanceExpenseCategory $category
 * @property string $description
 * @property string $amount
 * @property FinanceExpensePaymentStatus $payment_status
 * @property string|null $notes
 * @property int|null $converted_to_fin_loan_id
 * @property bool $excluded_as_liability
 */
class FinanceExpense extends Model
{
    protected $table = 'fin_expenses';

    protected $fillable = [
        'user_id',
        'fin_loan_id',
        'fin_loan_payment_id',
        'recurring_template_id',
        'expense_date',
        'category',
        'description',
        'amount',
        'payment_status',
        'notes',
        'converted_to_fin_loan_id',
        'excluded_as_liability',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
            'category' => FinanceExpenseCategory::class,
            'payment_status' => FinanceExpensePaymentStatus::class,
            'excluded_as_liability' => 'boolean',
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

    public function loanPayment(): BelongsTo
    {
        return $this->belongsTo(FinanceLoanPayment::class, 'fin_loan_payment_id');
    }

    public function recurringTemplate(): BelongsTo
    {
        return $this->belongsTo(FinanceRecurringExpense::class, 'recurring_template_id');
    }

    public function convertedLoan(): BelongsTo
    {
        return $this->belongsTo(FinanceLoan::class, 'converted_to_fin_loan_id');
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
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCountable(Builder $query): Builder
    {
        return $query->where('excluded_as_liability', false);
    }

    public function isLikelyMisfiledLiability(): bool
    {
        if ($this->excluded_as_liability || $this->fin_loan_id || $this->fin_loan_payment_id) {
            return false;
        }

        $amount = (float) $this->amount;
        $description = mb_strtolower($this->description.' '.($this->notes ?? ''));

        if ($amount >= 50000) {
            return true;
        }

        return str_contains($description, 'gold loan')
            || str_contains($description, 'loan repayment')
            || (str_contains($description, 'loan') && $amount >= 25000);
    }
}
