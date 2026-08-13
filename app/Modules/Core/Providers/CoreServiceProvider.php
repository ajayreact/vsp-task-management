<?php

namespace App\Modules\Core\Providers;

use Illuminate\Support\ServiceProvider;

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
    }
}
