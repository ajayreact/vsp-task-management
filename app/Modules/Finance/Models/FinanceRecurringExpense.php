<?php

namespace App\Modules\Finance\Models;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Enums\FinanceExpenseCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string $amount
 * @property FinanceExpenseCategory $category
 * @property int $due_day
 * @property bool $active
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property string|null $notes
 */
class FinanceRecurringExpense extends Model
{
    protected $table = 'fin_recurring_expenses';

    protected $fillable = [
        'user_id',
        'title',
        'amount',
        'category',
        'due_day',
        'active',
        'start_date',
        'end_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'category' => FinanceExpenseCategory::class,
            'due_day' => 'integer',
            'active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(FinanceExpense::class, 'recurring_template_id');
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
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function isActiveInPeriod(Carbon $periodStart, Carbon $periodEnd): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->start_date->gt($periodEnd)) {
            return false;
        }

        if ($this->end_date !== null && $this->end_date->lt($periodStart)) {
            return false;
        }

        return true;
    }
}
