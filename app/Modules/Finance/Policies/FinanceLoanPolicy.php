<?php

namespace App\Modules\Finance\Policies;

use App\Modules\Core\Models\User;
use App\Modules\Finance\Models\FinanceLoan;

class FinanceLoanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('viewMyFinance');
    }

    public function view(User $user, FinanceLoan $loan): bool
    {
        return $user->can('viewMyFinance') && $loan->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->can('viewMyFinance');
    }

    public function update(User $user, FinanceLoan $loan): bool
    {
        return $user->can('viewMyFinance') && $loan->user_id === $user->id;
    }

    public function delete(User $user, FinanceLoan $loan): bool
    {
        return $user->can('viewMyFinance') && $loan->user_id === $user->id;
    }

    public function recordPayment(User $user, FinanceLoan $loan): bool
    {
        return $user->can('viewMyFinance') && $loan->user_id === $user->id;
    }
}
