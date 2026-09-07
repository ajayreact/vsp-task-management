<?php

namespace App\Modules\Finance\Policies;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Models\FinanceRecurringExpense;

class FinanceRecurringExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewMyFinance');
    }

    public function view(User $user, FinanceRecurringExpense $recurring): bool
    {
        return $user->can('viewMyFinance') && $recurring->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('viewMyFinance');
    }

    public function update(User $user, FinanceRecurringExpense $recurring): bool
    {
        return $user->can('viewMyFinance') && $recurring->user_id === $user->id;
    }

    public function delete(User $user, FinanceRecurringExpense $recurring): bool
    {
        return $user->can('viewMyFinance') && $recurring->user_id === $user->id;
    }
}
