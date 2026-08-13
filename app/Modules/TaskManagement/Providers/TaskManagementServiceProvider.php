<?php

namespace App\Modules\TaskManagement\Providers;

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

        Route::middleware(['web', 'auth'])
            ->prefix('tasks')
            ->name('tasks.')
            ->group(base_path('routes/tasks.php'));
    }
}
