<?php

use App\Modules\Core\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Modules\Core\Http\Controllers\DashboardController;
use App\Modules\Core\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Shared routes
|--------------------------------------------------------------------------
|
| Only routes that belong to no single module live here. Task Management
| registers its own route file from its module service provider.
|
*/

Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('home');

Route::middleware(['auth', 'internal'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/feed', [NotificationController::class, 'feed'])->name('notifications.feed');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
});

/*
|--------------------------------------------------------------------------
| Short aliases
|--------------------------------------------------------------------------
|
| The real screens live under /admin and /tasks. These redirects exist so that
| typing /employees or /projects in the address bar still lands on the page,
| instead of a 404.
|
*/

Route::middleware(['auth', 'internal'])->group(function () {
    Route::permanentRedirect('/employees', '/admin/employees');
    Route::permanentRedirect('/departments', '/admin/departments');
    Route::permanentRedirect('/roles', '/admin/roles');
    Route::permanentRedirect('/clients', '/tasks/clients');
    Route::permanentRedirect('/work-clients', '/tasks/clients');
    Route::permanentRedirect('/companies', '/tasks/clients');
    Route::permanentRedirect('/tasks/companies', '/tasks/clients');
    Route::permanentRedirect('/projects', '/tasks/projects');
    Route::permanentRedirect('/tasks/open-board', '/tasks/board');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';

if (app()->environment('local')) {
    // TEMPORARY: delete this block and resources/js/Pages/Dev/_tmp-notification-sound.tsx after sound QA.
    Route::get('/_tmp/notification-sound', fn () => Inertia::render('Dev/_tmp-notification-sound'));
}
