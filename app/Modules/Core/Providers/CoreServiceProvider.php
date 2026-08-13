<?php

namespace App\Modules\Core\Providers;

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\Department;
use App\Modules\Core\Models\Employee;
use App\Modules\Core\Models\User;
use App\Modules\Core\Policies\DepartmentPolicy;
use App\Modules\Core\Policies\EmployeePolicy;
use App\Modules\Core\Policies\RolePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

/**
 * Shared kernel: authentication, employees, departments, roles and
 * permissions, notifications, file storage and the activity log.
 *
 * Nothing registered here may reference the CRM or Task Management modules.
 * Both modules depend on Core; Core depends on neither.
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
        Gate::before(function (User $user): ?bool {
            return $user->hasRole(SystemRole::SuperAdmin->value) ? true : null;
        });
    }
}
