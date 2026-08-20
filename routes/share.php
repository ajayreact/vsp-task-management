<?php

use App\Modules\TaskManagement\Http\Controllers\DeliverableShareController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public deliverable share links
|--------------------------------------------------------------------------
|
| Guest-accessible. Registered by TaskManagementServiceProvider without
| auth or tasks.access. Short codes are the preferred public identifier;
| legacy 64-char tokens remain supported for backward compatibility.
|
*/

Route::prefix('d')->name('share.short.')->group(function () {
    Route::get('{shortCode}', [DeliverableShareController::class, 'showShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('show');

    Route::post('{shortCode}/approve', [DeliverableShareController::class, 'approveShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('approve');

    Route::post('{shortCode}/request-changes', [DeliverableShareController::class, 'requestChangesShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('request-changes');

    Route::get('{shortCode}/files/{mediaUuid}', [DeliverableShareController::class, 'fileShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->where('mediaUuid', '[0-9a-fA-F-]{36}')
        ->name('file');
});

Route::get('share/{token}', [DeliverableShareController::class, 'show'])
    ->where('token', '[0-9a-f]{64}')
    ->name('share.show');

Route::post('share/{token}/approve', [DeliverableShareController::class, 'approve'])
    ->where('token', '[0-9a-f]{64}')
    ->name('share.approve');

Route::post('share/{token}/request-changes', [DeliverableShareController::class, 'requestChanges'])
    ->where('token', '[0-9a-f]{64}')
    ->name('share.request-changes');

Route::get('share/{token}/files/{mediaUuid}', [DeliverableShareController::class, 'file'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('share.file');
