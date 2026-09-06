<?php

namespace App\Modules\Finance\Policies;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Models\FinanceIncome;

class FinanceIncomePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewMyFinance');
    }

    public function view(User $user, FinanceIncome $income): bool
    {
        return $user->can('viewMyFinance') && $income->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('viewMyFinance');
    }

    public function update(User $user, FinanceIncome $income): bool
    {
        return $user->can('viewMyFinance') && $income->user_id === $user->id;
    }

    public function delete(User $user, FinanceIncome $income): bool
    {
        return $user->can('viewMyFinance') && $income->user_id === $user->id;
    }
}
