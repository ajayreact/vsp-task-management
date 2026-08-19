<?php

use App\Modules\Attendance\Http\Controllers\AttendanceBreakResumeController;
use App\Modules\Attendance\Http\Controllers\AttendanceBreakStartController;
use App\Modules\Attendance\Http\Controllers\AttendanceCheckInController;
use App\Modules\Attendance\Http\Controllers\AttendanceCheckOutController;
use App\Modules\Attendance\Http\Controllers\AttendanceLocationVerificationController;
use App\Modules\Attendance\Http\Controllers\AttendanceMarkController;
use Illuminate\Support\Facades\Route;

Route::get('mark', [AttendanceMarkController::class, 'show'])->name('mark');
Route::post('check-in', AttendanceCheckInController::class)->name('check-in');
Route::post('check-out', AttendanceCheckOutController::class)->name('check-out');
Route::post('break/start', AttendanceBreakStartController::class)->name('break.start');
Route::post('break/resume', AttendanceBreakResumeController::class)->name('break.resume');
Route::post('verify-location', [AttendanceLocationVerificationController::class, 'store'])->name('verify-location');
