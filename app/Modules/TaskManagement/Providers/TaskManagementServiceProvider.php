<?php

namespace App\Modules\TaskManagement\Providers;

use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Models\Company;
use App\Modules\TaskManagement\Models\Project;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Policies\CompanyPolicy;
use App\Modules\TaskManagement\Policies\ProjectPolicy;
use App\Modules\TaskManagement\Policies\TaskPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Internal task management: companies, projects, tasks, assignment and
 * acceptance, availability, workload, the work timer, timesheets and
 * creative review.
 *
 * Owns every `tm_*` table. Must never reference the CRM module.
 */
class TaskManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/tasks'));

        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Project::class, ProjectPolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);

        Route::middleware(['web', 'auth', 'internal', 'permission:'.Ability::AccessTasks->value])
            ->prefix('tasks')
            ->name('tasks.')
            ->group(base_path('routes/tasks.php'));
    }
}
