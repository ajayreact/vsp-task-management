<?php

use App\Modules\Core\Http\Controllers\Admin\DepartmentController;
use App\Modules\Core\Http\Controllers\Admin\EmployeeController;
use App\Modules\Core\Http\Controllers\Admin\RoleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core administration routes
|--------------------------------------------------------------------------
|
| Registered by CoreServiceProvider under the "admin" prefix. These manage
| the shared kernel — people, structure and access — so they belong to no
| single module. Authorization is enforced per action by policies.
|
*/

Route::resource('employees', EmployeeController::class)->except('show');
Route::resource('departments', DepartmentController::class)->only(['index', 'store', 'update', 'destroy']);
Route::resource('roles', RoleController::class)->except('show');
