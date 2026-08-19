<?php

namespace App\Modules\Attendance\Providers;

use App\Modules\Core\Enums\SystemRole;
use App\Modules\Core\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Attendance tracking for Super Admin. Check-in flows and verification will
 * land here later; for now the module owns schema and the overview dashboard.
 */
class AttendanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/attendance'));

        Gate::define('viewAttendance', function (User $user): bool {
            return $user->hasRole(SystemRole::SuperAdmin->value);
        });

        Gate::define('markOwnAttendance', function (User $user): bool {
            return $user->employee !== null;
        });

        Route::middleware(['web', 'auth', 'internal'])
            ->prefix('admin')
            ->name('admin.')
            ->group(base_path('routes/attendance.php'));

        Route::middleware(['web', 'auth', 'internal'])
            ->prefix('attendance')
            ->name('attendance.')
            ->group(base_path('routes/attendance-employee.php'));
    }
}
