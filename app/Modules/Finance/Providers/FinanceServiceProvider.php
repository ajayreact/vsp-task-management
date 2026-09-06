<?php

namespace App\Modules\Finance\Providers;

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceIncome;
use App\Modules\Finance\Models\FinanceLoan;
use App\Modules\Finance\Policies\FinanceExpensePolicy;
use App\Modules\Finance\Policies\FinanceIncomePolicy;
use App\Modules\Finance\Policies\FinanceLoanPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Private personal finance module for Super Admin / Operations Head only.
 */
class FinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/finance'));

        Gate::define('viewMyFinance', function (User $user): bool {
            return $user->hasRole(SystemRole::SuperAdmin->value);
        });

        Gate::policy(FinanceIncome::class, FinanceIncomePolicy::class);
        Gate::policy(FinanceExpense::class, FinanceExpensePolicy::class);
        Gate::policy(FinanceLoan::class, FinanceLoanPolicy::class);

        Route::middleware(['web', 'auth', 'internal'])
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/finance.php'));
    }
}
