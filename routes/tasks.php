<?php

use App\Modules\TaskManagement\Http\Controllers\AvailabilityController;
use App\Modules\TaskManagement\Http\Controllers\CompanyController;
use App\Modules\TaskManagement\Http\Controllers\CompanyLogoLibraryController;
use App\Modules\TaskManagement\Http\Controllers\DeliverableController;
use App\Modules\TaskManagement\Http\Controllers\NotificationSoundController;
use App\Modules\TaskManagement\Http\Controllers\OpenBoardController;
use App\Modules\TaskManagement\Http\Controllers\ProjectController;
use App\Modules\TaskManagement\Http\Controllers\SettingsController;
use App\Modules\TaskManagement\Http\Controllers\TaskAttachmentController;
use App\Modules\TaskManagement\Http\Controllers\TaskChecklistItemController;
use App\Modules\TaskManagement\Http\Controllers\TaskCommentController;
use App\Modules\TaskManagement\Http\Controllers\TaskController;
use App\Modules\TaskManagement\Http\Controllers\TaskRecurrenceController;
use App\Modules\TaskManagement\Http\Controllers\TaskReminderController;
use App\Modules\TaskManagement\Http\Controllers\TaskSubtaskController;
use App\Modules\TaskManagement\Http\Controllers\TaskWorkflowController;
use App\Modules\TaskManagement\Http\Controllers\TimeEntryController;
use App\Modules\TaskManagement\Http\Controllers\TimerController;
use App\Modules\TaskManagement\Http\Controllers\TimesheetController;
use App\Modules\TaskManagement\Http\Controllers\WorkloadController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Task Management module routes
|--------------------------------------------------------------------------
|
| Registered by TaskManagementServiceProvider under the "tasks" prefix and
| "tasks." route name prefix, behind the internal-staff check and the
| tasks.access permission. For internal employees and managers only.
|
| Tasks sit at the root of the group rather than being a nested resource, so
| the URLs read /tasks/14 rather than /tasks/tasks/14. The literal segments
| below are declared first and the task routes are constrained to numbers, so
| /tasks/board can never be mistaken for a task id.
|
| Nothing here may reference a CRM route, controller or model.
|
*/

Route::get('board', OpenBoardController::class)->name('board');

Route::get('notification-sound/custom', [NotificationSoundController::class, 'custom'])->name('notification-sound.custom');

Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
Route::put('settings/notification-sound', [SettingsController::class, 'updateNotificationSound'])->name('settings.notification-sound.update');
Route::post('settings/notification-sound/custom', [SettingsController::class, 'uploadCustomNotificationSound'])->name('settings.notification-sound.custom.upload');
Route::delete('settings/notification-sound/custom', [SettingsController::class, 'deleteCustomNotificationSound'])->name('settings.notification-sound.custom.delete');

Route::get('workload', [WorkloadController::class, 'index'])->name('workload');
Route::get('workload/export/excel', [WorkloadController::class, 'exportExcel'])->name('workload.export.excel');
Route::get('workload/export/pdf', [WorkloadController::class, 'exportPdf'])->name('workload.export.pdf');

Route::get('availability', [AvailabilityController::class, 'index'])->name('availability.index');
Route::post('availability', [AvailabilityController::class, 'store'])->name('availability.store');
Route::post('availability/capacity', [AvailabilityController::class, 'storeCapacity'])->name('availability.capacity');
Route::delete('availability/{availability}', [AvailabilityController::class, 'destroy'])->name('availability.destroy');

Route::get('timesheets/export/excel', [TimesheetController::class, 'exportExcel'])->name('timesheets.export.excel');
Route::get('timesheets/export/pdf', [TimesheetController::class, 'exportPdf'])->name('timesheets.export.pdf');
Route::get('timesheets', [TimesheetController::class, 'index'])->name('timesheets.index');
Route::get('timesheets/{timesheet}', [TimesheetController::class, 'show'])->name('timesheets.show');
Route::post('timesheets/{timesheet}/submit', [TimesheetController::class, 'submit'])->name('timesheets.submit');
Route::post('timesheets/{timesheet}/approve', [TimesheetController::class, 'approve'])->name('timesheets.approve');
Route::post('timesheets/{timesheet}/reject', [TimesheetController::class, 'reject'])->name('timesheets.reject');

Route::delete('time-entries/{entry}', [TimeEntryController::class, 'destroy'])->name('time-entries.destroy');
Route::post('deliverables/{deliverable}/review', [DeliverableController::class, 'review'])->name('deliverables.review');
Route::post('deliverables/{deliverable}/share-link', [DeliverableController::class, 'shareLink'])->name('deliverables.share-link');

Route::get('clients/export/excel', [CompanyController::class, 'exportExcel'])->name('clients.export.excel');
Route::get('clients/export/pdf', [CompanyController::class, 'exportPdf'])->name('clients.export.pdf');
Route::resource('clients', CompanyController::class)
    ->parameters(['clients' => 'company'])
    ->only(['index', 'store', 'update', 'destroy']);

Route::get('logo-library', [CompanyLogoLibraryController::class, 'index'])->name('logo-library.index');
Route::get('logo-library/{company}', [CompanyLogoLibraryController::class, 'show'])->name('logo-library.show');
Route::put('logo-library/{company}', [CompanyLogoLibraryController::class, 'update'])->name('logo-library.update');
Route::post('logo-library/{company}/logos', [CompanyLogoLibraryController::class, 'storeLogo'])->name('logo-library.logos.store');
Route::get('logo-library/{company}/logos/{media}/preview', [CompanyLogoLibraryController::class, 'previewLogo'])->name('logo-library.logos.preview');
Route::get('logo-library/{company}/logos/{media}/download', [CompanyLogoLibraryController::class, 'downloadLogo'])->name('logo-library.logos.download');
Route::delete('logo-library/{company}/logos/{media}', [CompanyLogoLibraryController::class, 'destroyLogo'])->name('logo-library.logos.destroy');
Route::post('logo-library/{company}/share-link', [CompanyLogoLibraryController::class, 'shareLink'])->name('logo-library.share-link');

Route::get('projects/export/excel', [ProjectController::class, 'exportExcel'])->name('projects.export.excel');
Route::get('projects/export/pdf', [ProjectController::class, 'exportPdf'])->name('projects.export.pdf');
Route::resource('projects', ProjectController::class);

Route::get('/', [TaskController::class, 'index'])->name('index');
Route::get('export/excel', [TaskController::class, 'exportExcel'])->name('export.excel');
Route::get('export/pdf', [TaskController::class, 'exportPdf'])->name('export.pdf');
Route::get('create', [TaskController::class, 'create'])->name('create');
Route::post('/', [TaskController::class, 'store'])->name('store');

Route::whereNumber('task')->group(function () {
    Route::get('{task}', [TaskController::class, 'show'])->name('show');
    Route::get('{task}/edit', [TaskController::class, 'edit'])->name('edit');
    Route::put('{task}', [TaskController::class, 'update'])->name('update');
    Route::delete('{task}', [TaskController::class, 'destroy'])->name('destroy');

    // Workflow rather than CRUD: these move a task between people.
    Route::controller(TaskWorkflowController::class)->group(function () {
        Route::post('{task}/publish', 'publish')->name('publish');
        Route::post('{task}/assign', 'assign')->name('assign');
        Route::post('{task}/claim', 'claim')->name('claim');
        Route::post('{task}/accept', 'accept')->name('accept');
        Route::post('{task}/decline', 'decline')->name('decline');
        Route::post('{task}/status', 'status')->name('status');
    });

    Route::post('{task}/attachments', [TaskAttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('{task}/attachments/{media}', [TaskAttachmentController::class, 'destroy'])->name('attachments.destroy');

    Route::post('{task}/comments', [TaskCommentController::class, 'store'])->name('comments.store');
    Route::put('{task}/comments/{comment}', [TaskCommentController::class, 'update'])->name('comments.update');
    Route::delete('{task}/comments/{comment}', [TaskCommentController::class, 'destroy'])->name('comments.destroy');

    Route::post('{task}/checklist-items', [TaskChecklistItemController::class, 'store'])->name('checklist-items.store');
    Route::put('{task}/checklist-items/{item}', [TaskChecklistItemController::class, 'update'])->name('checklist-items.update');
    Route::patch('{task}/checklist-items/{item}/toggle', [TaskChecklistItemController::class, 'toggle'])->name('checklist-items.toggle');
    Route::post('{task}/checklist-items/reorder', [TaskChecklistItemController::class, 'reorder'])->name('checklist-items.reorder');
    Route::delete('{task}/checklist-items/{item}', [TaskChecklistItemController::class, 'destroy'])->name('checklist-items.destroy');

    Route::post('{task}/subtasks', [TaskSubtaskController::class, 'store'])->name('subtasks.store');
    Route::put('{task}/subtasks/{subtask}', [TaskSubtaskController::class, 'update'])->name('subtasks.update');
    Route::patch('{task}/subtasks/{subtask}/toggle', [TaskSubtaskController::class, 'toggle'])->name('subtasks.toggle');
    Route::post('{task}/subtasks/reorder', [TaskSubtaskController::class, 'reorder'])->name('subtasks.reorder');
    Route::delete('{task}/subtasks/{subtask}', [TaskSubtaskController::class, 'destroy'])->name('subtasks.destroy');

    Route::post('{task}/reminders', [TaskReminderController::class, 'store'])->name('reminders.store');
    Route::delete('{task}/reminders/{reminder}', [TaskReminderController::class, 'destroy'])->name('reminders.destroy');

    Route::put('{task}/recurrence', [TaskRecurrenceController::class, 'upsert'])->name('recurrence.upsert');

    Route::post('{task}/timer/start', [TimerController::class, 'start'])->name('timer.start');
    Route::post('{task}/timer/pause', [TimerController::class, 'pause'])->name('timer.pause');
    Route::post('{task}/timer/stop', [TimerController::class, 'stop'])->name('timer.stop');
    Route::post('{task}/time-entries', [TimeEntryController::class, 'store'])->name('time-entries.store');
    Route::post('{task}/deliverables', [DeliverableController::class, 'store'])->name('deliverables.store');
});
