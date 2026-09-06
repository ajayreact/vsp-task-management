<?php

use App\Modules\TaskManagement\Http\Controllers\CompanyDocumentShareController;
use App\Modules\TaskManagement\Http\Controllers\CompanyShareController;
use App\Modules\TaskManagement\Http\Controllers\ContentCalendarShareController;
use App\Modules\TaskManagement\Http\Controllers\ContractShareController;
use App\Modules\TaskManagement\Http\Controllers\DeliverableShareController;
use App\Modules\TaskManagement\Http\Controllers\SharePreviewImageController;
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

// Open Graph / WhatsApp preview image (public, no auth). Cache-busted via ?v=.
Route::get('share-preview/og-image.png', SharePreviewImageController::class)
    ->name('share-preview.og-image');

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

    Route::get('{shortCode}/files/{mediaUuid}/download', [DeliverableShareController::class, 'downloadFileShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->where('mediaUuid', '[0-9a-fA-F-]{36}')
        ->name('file.download');
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

Route::get('share/{token}/files/{mediaUuid}/download', [DeliverableShareController::class, 'downloadFile'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('share.file.download');

Route::prefix('c')->name('company-share.short.')->group(function () {
    Route::get('{shortCode}', [CompanyShareController::class, 'showShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('show');

    Route::get('{shortCode}/files/{mediaUuid}', [CompanyShareController::class, 'fileShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->where('mediaUuid', '[0-9a-fA-F-]{36}')
        ->name('file');

    Route::get('{shortCode}/files/{mediaUuid}/download', [CompanyShareController::class, 'downloadFileShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->where('mediaUuid', '[0-9a-fA-F-]{36}')
        ->name('file.download');
});

Route::get('company-share/{token}', [CompanyShareController::class, 'show'])
    ->where('token', '[0-9a-f]{64}')
    ->name('company-share.show');

Route::get('company-share/{token}/files/{mediaUuid}', [CompanyShareController::class, 'file'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('company-share.file');

Route::get('company-share/{token}/files/{mediaUuid}/download', [CompanyShareController::class, 'downloadFile'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('company-share.file.download');

Route::prefix('od')->name('document-share.short.')->group(function () {
    Route::get('{shortCode}', [CompanyDocumentShareController::class, 'showShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('show');

    Route::get('{shortCode}/files/{mediaUuid}', [CompanyDocumentShareController::class, 'fileShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->where('mediaUuid', '[0-9a-fA-F-]{36}')
        ->name('file');

    Route::get('{shortCode}/files/{mediaUuid}/download', [CompanyDocumentShareController::class, 'downloadFileShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->where('mediaUuid', '[0-9a-fA-F-]{36}')
        ->name('file.download');
});

Route::get('document-share/{token}', [CompanyDocumentShareController::class, 'show'])
    ->where('token', '[0-9a-f]{64}')
    ->name('document-share.show');

Route::get('document-share/{token}/files/{mediaUuid}', [CompanyDocumentShareController::class, 'file'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('document-share.file');

Route::get('document-share/{token}/files/{mediaUuid}/download', [CompanyDocumentShareController::class, 'downloadFile'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('document-share.file.download');

Route::prefix('cc')->name('content-share.short.')->group(function () {
    Route::get('{shortCode}', [ContentCalendarShareController::class, 'showItemShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('show');

    Route::post('{shortCode}/approve', [ContentCalendarShareController::class, 'approveItemShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('approve');

    Route::post('{shortCode}/request-changes', [ContentCalendarShareController::class, 'requestChangesItemShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('request-changes');

    Route::get('{shortCode}/files/{mediaUuid}', [ContentCalendarShareController::class, 'fileItemShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->where('mediaUuid', '[0-9a-fA-F-]{36}')
        ->name('file');

    Route::get('{shortCode}/files/{mediaUuid}/download', [ContentCalendarShareController::class, 'downloadItemFileShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->where('mediaUuid', '[0-9a-fA-F-]{36}')
        ->name('file.download');
});

Route::get('content-share/{token}', [ContentCalendarShareController::class, 'showItem'])
    ->where('token', '[0-9a-f]{64}')
    ->name('content-share.show');

Route::post('content-share/{token}/approve', [ContentCalendarShareController::class, 'approveItem'])
    ->where('token', '[0-9a-f]{64}')
    ->name('content-share.approve');

Route::post('content-share/{token}/request-changes', [ContentCalendarShareController::class, 'requestChangesItem'])
    ->where('token', '[0-9a-f]{64}')
    ->name('content-share.request-changes');

Route::get('content-share/{token}/files/{mediaUuid}', [ContentCalendarShareController::class, 'fileItem'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('content-share.file');

Route::get('content-share/{token}/files/{mediaUuid}/download', [ContentCalendarShareController::class, 'downloadItemFile'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('content-share.file.download');

Route::prefix('cs')->name('content-schedule-share.short.')->group(function () {
    Route::get('{shortCode}', [ContentCalendarShareController::class, 'showScheduleShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('show');

    Route::get('{shortCode}/files/{mediaUuid}', [ContentCalendarShareController::class, 'fileScheduleShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->where('mediaUuid', '[0-9a-fA-F-]{36}')
        ->name('file');

    Route::get('{shortCode}/files/{mediaUuid}/download', [ContentCalendarShareController::class, 'downloadScheduleFileShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->where('mediaUuid', '[0-9a-fA-F-]{36}')
        ->name('file.download');
});

Route::get('content-schedule-share/{token}', [ContentCalendarShareController::class, 'showSchedule'])
    ->where('token', '[0-9a-f]{64}')
    ->name('content-schedule-share.show');

Route::get('content-schedule-share/{token}/files/{mediaUuid}', [ContentCalendarShareController::class, 'fileSchedule'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('content-schedule-share.file');

Route::get('content-schedule-share/{token}/files/{mediaUuid}/download', [ContentCalendarShareController::class, 'downloadScheduleFile'])
    ->where('token', '[0-9a-f]{64}')
    ->where('mediaUuid', '[0-9a-fA-F-]{36}')
    ->name('content-schedule-share.file.download');

Route::prefix('ct')->name('contract-share.short.')->group(function () {
    Route::get('{shortCode}', [ContractShareController::class, 'showShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('show');

    Route::post('{shortCode}/sign', [ContractShareController::class, 'signShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('sign');

    Route::get('{shortCode}/pdf', [ContractShareController::class, 'pdfShort'])
        ->where('shortCode', '[A-Za-z0-9]{8,10}')
        ->name('pdf');
});

Route::get('contract-share/{token}', [ContractShareController::class, 'show'])
    ->where('token', '[0-9a-f]{64}')
    ->name('contract-share.show');

Route::post('contract-share/{token}/sign', [ContractShareController::class, 'sign'])
    ->where('token', '[0-9a-f]{64}')
    ->name('contract-share.sign');

Route::get('contract-share/{token}/pdf', [ContractShareController::class, 'pdf'])
    ->where('token', '[0-9a-f]{64}')
    ->name('contract-share.pdf');

/*
| Preferred Creative Review URLs: /{client-slug}/{shortCode}
| Registered last so reserved prefixes (d, c, docs, ci, cs, ct, share, …) win.
| The slug is decorative; shortCode remains the real identifier.
| Legacy /d/{shortCode} routes stay active for existing shares.
*/
Route::prefix('{companySlug}')
    ->where([
        'companySlug' => '(?!(?:d|c|cc|od|docs|ci|cs|ct|share|share-preview|content-share|content-schedule-share|contract-share|tasks|admin|login|logout|dashboard|notifications|settings|api|up)(?:-|$))[a-z0-9]+(?:-[a-z0-9]+)*',
    ])
    ->name('share.client.')
    ->group(function () {
        Route::get('{shortCode}', [DeliverableShareController::class, 'showClient'])
            ->where('shortCode', '[A-Za-z0-9]{8,10}')
            ->name('show');

        Route::post('{shortCode}/approve', [DeliverableShareController::class, 'approveClient'])
            ->where('shortCode', '[A-Za-z0-9]{8,10}')
            ->name('approve');

        Route::post('{shortCode}/request-changes', [DeliverableShareController::class, 'requestChangesClient'])
            ->where('shortCode', '[A-Za-z0-9]{8,10}')
            ->name('request-changes');

        Route::get('{shortCode}/files/{mediaUuid}', [DeliverableShareController::class, 'fileClient'])
            ->where('shortCode', '[A-Za-z0-9]{8,10}')
            ->where('mediaUuid', '[0-9a-fA-F-]{36}')
            ->name('file');

        Route::get('{shortCode}/files/{mediaUuid}/download', [DeliverableShareController::class, 'downloadFileClient'])
            ->where('shortCode', '[A-Za-z0-9]{8,10}')
            ->where('mediaUuid', '[0-9a-fA-F-]{36}')
            ->name('file.download');
    });
