<?php

use App\Modules\TaskManagement\Http\Controllers\CompanyController;
use App\Modules\TaskManagement\Http\Controllers\OpenBoardController;
use App\Modules\TaskManagement\Http\Controllers\ProjectController;
use App\Modules\TaskManagement\Http\Controllers\TaskController;
use App\Modules\TaskManagement\Http\Controllers\TaskWorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Task Management module routes
|--------------------------------------------------------------------------
|
| Registered by TaskManagementServiceProvider under the "tasks" prefix and
| "tasks." route name prefix, behind the internal-staff check and the
| tasks.access permission. For internal employees and managers only.
|
| Tasks sit at the root of the group rather than being a nested resource, so
| the URLs read /tasks/14 rather than /tasks/tasks/14. The literal segments
| below are declared first and the task routes are constrained to numbers, so
| /tasks/board can never be mistaken for a task id.
|
| Nothing here may reference a CRM route, controller or model.
|
*/

Route::get('board', OpenBoardController::class)->name('board');

Route::resource('companies', CompanyController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('projects', ProjectController::class);

Route::get('/', [TaskController::class, 'index'])->name('index');
Route::get('create', [TaskController::class, 'create'])->name('create');
Route::post('/', [TaskController::class, 'store'])->name('store');

Route::whereNumber('task')->group(function () {
    Route::get('{task}', [TaskController::class, 'show'])->name('show');
    Route::get('{task}/edit', [TaskController::class, 'edit'])->name('edit');
    Route::put('{task}', [TaskController::class, 'update'])->name('update');
    Route::delete('{task}', [TaskController::class, 'destroy'])->name('destroy');

    // Workflow rather than CRUD: these move a task between people.
    Route::controller(TaskWorkflowController::class)->group(function () {
        Route::post('{task}/publish', 'publish')->name('publish');
        Route::post('{task}/assign', 'assign')->name('assign');
        Route::post('{task}/claim', 'claim')->name('claim');
        Route::post('{task}/accept', 'accept')->name('accept');
        Route::post('{task}/decline', 'decline')->name('decline');
        Route::post('{task}/status', 'status')->name('status');
    });
});
