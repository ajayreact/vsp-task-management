<?php

namespace App\Modules\Finance\Policies;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Models\FinanceExpense;

class FinanceExpensePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewMyFinance');
    }

    public function view(User $user, FinanceExpense $expense): bool
    {
        return $user->can('viewMyFinance') && $expense->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('viewMyFinance');
    }

    public function update(User $user, FinanceExpense $expense): bool
    {
        return $user->can('viewMyFinance') && $expense->user_id === $user->id;
    }

    public function delete(User $user, FinanceExpense $expense): bool
    {
        return $user->can('viewMyFinance') && $expense->user_id === $user->id;
    }
}
