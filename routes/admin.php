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

Route::get('employees/export/excel', [EmployeeController::class, 'exportExcel'])->name('employees.export.excel');
Route::get('employees/export/pdf', [EmployeeController::class, 'exportPdf'])->name('employees.export.pdf');
Route::resource('employees', EmployeeController::class)->except('show');

Route::get('departments/export/excel', [DepartmentController::class, 'exportExcel'])->name('departments.export.excel');
Route::get('departments/export/pdf', [DepartmentController::class, 'exportPdf'])->name('departments.export.pdf');
Route::resource('departments', DepartmentController::class)->only(['index', 'store', 'update', 'destroy']);

Route::get('roles/export/excel', [RoleController::class, 'exportExcel'])->name('roles.export.excel');
Route::get('roles/export/pdf', [RoleController::class, 'exportPdf'])->name('roles.export.pdf');
Route::resource('roles', RoleController::class)->except('show');
