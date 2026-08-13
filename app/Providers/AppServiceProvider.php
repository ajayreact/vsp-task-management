<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->resolveFactoriesPerModule();
    }

    /**
     * Models live under `App\Modules\{Module}\Models`, which Laravel's default
     * convention cannot map to a factory. Mirror the module in the factory
     * namespace instead:
     *
     *     App\Modules\Crm\Models\Lead  ->  Database\Factories\Crm\LeadFactory
     */
    protected function resolveFactoriesPerModule(): void
    {
        Factory::guessFactoryNamesUsing(function (string $modelName): string {
            $relative = Str::after($modelName, 'App\\Modules\\');
            $module = Str::before($relative, '\\Models\\');
            $model = Str::afterLast($relative, '\\');

            return "Database\\Factories\\{$module}\\{$model}Factory";
        });
    }
}
