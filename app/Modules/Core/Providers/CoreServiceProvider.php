<?php

namespace App\Modules\Core\Providers;

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\Core\Policies\DepartmentPolicy;
use App\Modules\Core\Policies\EmployeePolicy;
use App\Modules\Core\Policies\RolePolicy;
use App\Modules\Finance\Models\FinanceExpense;
use App\Modules\Finance\Models\FinanceIncome;
use App\Modules\Finance\Models\FinanceLoan;
use App\Modules\Finance\Models\FinanceLoanPayment;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

/**
 * Shared kernel: authentication, employees, departments, roles and
 * permissions, notifications, file storage and the activity log.
 *
 * Nothing registered here may reference Task Management. Task Management
 * depends on Core; Core depends on neither other business module.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/core'));

        $this->registerRoutes();
        $this->registerPolicies();
        $this->grantSuperAdminEverything();
    }

    protected function registerRoutes(): void
    {
        Route::middleware(['web', 'auth', 'internal'])
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/admin.php'));
    }

    protected function registerPolicies(): void
    {
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
    }

    /**
     * Super admin bypasses every check, so new abilities do not have to be
     * backfilled onto the role each time one is added.
     */
    protected function grantSuperAdminEverything(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            if (! $user->hasRole(SystemRole::SuperAdmin->value)) {
                return null;
            }

            $model = $arguments[0] ?? null;

            // Employee-specific task workflow actions must still evaluate TaskPolicy.
            if ($model instanceof Task
                && in_array($ability, ['respond', 'claim', 'assign'], true)) {
                return null;
            }

            // Personal finance records stay private even between Super Admin accounts.
            if ($model instanceof FinanceIncome
                || $model instanceof FinanceExpense
                || $model instanceof FinanceLoan
                || $model instanceof FinanceLoanPayment) {
                return null;
            }

            return true;
        });
    }
}
