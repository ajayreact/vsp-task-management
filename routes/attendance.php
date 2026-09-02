<?php

use App\Modules\Attendance\Http\Controllers\AttendanceDashboardController;
use App\Modules\Attendance\Http\Controllers\OfficeLocationController;
use App\Modules\Attendance\Http\Controllers\WfhManagementController;
use Illuminate\Support\Facades\Route;

Route::get('attendance', [AttendanceDashboardController::class, '__invoke'])->name('attendance.index');
Route::get('attendance/export/monthly', [AttendanceDashboardController::class, 'exportMonthly'])->name('attendance.export.monthly');
Route::get('attendance/wfh', [WfhManagementController::class, 'index'])->name('attendance.wfh.index');
Route::post('attendance/wfh/{wfhRequest}/approve', [WfhManagementController::class, 'approve'])->name('attendance.wfh.approve');
Route::post('attendance/wfh/{wfhRequest}/reject', [WfhManagementController::class, 'reject'])->name('attendance.wfh.reject');

Route::prefix('attendance/offices')->name('attendance.offices.')->group(function () {
    Route::get('/', [OfficeLocationController::class, 'index'])->name('index');
    Route::get('create', [OfficeLocationController::class, 'create'])->name('create');
    Route::post('/', [OfficeLocationController::class, 'store'])->name('store');
    Route::get('{office_location}/edit', [OfficeLocationController::class, 'edit'])->name('edit');
    Route::put('{office_location}', [OfficeLocationController::class, 'update'])->name('update');
    Route::post('{office_location}/deactivate', [OfficeLocationController::class, 'deactivate'])->name('deactivate');
    Route::delete('{office_location}', [OfficeLocationController::class, 'destroy'])->name('destroy');
});
