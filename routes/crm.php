<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| CRM module routes
|--------------------------------------------------------------------------
|
| Registered by CrmServiceProvider under the "crm" prefix and "crm." route
| name prefix, for internal staff only. Client-facing routes live in
| routes/portal.php and must never be mixed in here.
|
*/

Route::get('/', function () {
    return Inertia::render('Crm/dashboard');
})->name('dashboard');
