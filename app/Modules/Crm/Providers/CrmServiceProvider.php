<?php

namespace App\Modules\Crm\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * CRM and campaign management: clients, ad channels, campaigns, leads,
 * follow-ups, the sales pipeline, conversions and client reporting.
 *
 * Owns every `crm_*` table. Must never reference the Task Management module.
 */
class CrmServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/crm'));

        $this->registerStaffRoutes();
        $this->registerPortalRoutes();
    }

    /**
     * Internal staff working campaigns and leads.
     */
    protected function registerStaffRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('crm')
            ->name('crm.')
            ->group(base_path('routes/crm.php'));
    }

    /**
     * External client users. Kept in a separate group so a portal request can
     * never reach a staff controller.
     */
    protected function registerPortalRoutes(): void
    {
        Route::middleware(['web', 'auth'])
            ->prefix('portal')
            ->name('portal.')
            ->group(base_path('routes/portal.php'));
    }
}
