<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Client portal routes
|--------------------------------------------------------------------------
|
| Registered by CrmServiceProvider under the "portal" prefix and "portal."
| route name prefix. Every route here is reachable only by client users and
| is scoped to a single crm_client_id. Staff-facing CRM routes live in
| routes/crm.php.
|
*/

Route::get('/', function () {
    return Inertia::render('Crm/Portal/dashboard');
})->name('dashboard');
