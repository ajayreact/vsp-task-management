<?php

use App\Modules\TaskManagement\Http\Controllers\DeliverableShareController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public deliverable share links
|--------------------------------------------------------------------------
|
| Guest-accessible. Registered by TaskManagementServiceProvider without
| auth or tasks.access. The token is the only public identifier.
|
*/

Route::get('share/{token}', [DeliverableShareController::class, 'show'])
    ->where('token', '[0-9a-f]{64}')
    ->name('share.show');

Route::get('share/{token}/files/{mediaUuid}', [DeliverableShareController::class, 'file'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('share.file');
