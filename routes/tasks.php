<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Task Management module routes
|--------------------------------------------------------------------------
|
| Registered by TaskManagementServiceProvider under the "tasks" prefix and
| "tasks." route name prefix, for internal employees and managers.
|
*/

Route::get('/', function () {
    return Inertia::render('TaskManagement/dashboard');
})->name('dashboard');
