<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Http\Requests\NotificationSoundSettingsRequest;
use App\Modules\TaskManagement\Http\Requests\NotificationSoundUploadRequest;
use App\Modules\TaskManagement\Http\Requests\ProofRetentionRequest;
use App\Modules\TaskManagement\Services\TaskManagementNotificationSoundService;
use App\Modules\TaskManagement\Services\TaskManagementRetentionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function edit(
        TaskManagementRetentionService $retention,
        TaskManagementNotificationSoundService $sounds,
    ): Response {
        $this->authorize('manageTaskManagementSettings');

        return Inertia::render('TaskManagement/settings/index', [
            'retention' => $retention->policy(),
            'notificationSound' => $sounds->settingsPayload(),
        ]);
    }

    public function update(ProofRetentionRequest $request, TaskManagementRetentionService $retention): RedirectResponse
    {
        $enabled = $request->boolean('enabled');

        $retention->writePolicy(
            $enabled,
            $enabled ? $request->integer('days') : null,
        );

        return back()->with('success', 'Proof file retention updated.');
    }

    public function updateNotificationSound(
        NotificationSoundSettingsRequest $request,
        TaskManagementNotificationSoundService $sounds,
    ): RedirectResponse {
        $sounds->writePolicy(
            $request->boolean('enabled'),
            $request->string('source')->toString(),
            $request->string('system_sound')->toString(),
        );

        return back()->with('success', 'Notification sound settings updated.');
    }

    public function uploadCustomNotificationSound(
        NotificationSoundUploadRequest $request,
        TaskManagementNotificationSoundService $sounds,
    ): RedirectResponse {
        $sounds->uploadCustomSound($request->file('sound'));

        return back()->with('success', 'Custom notification sound uploaded.');
    }

    public function deleteCustomNotificationSound(
        TaskManagementNotificationSoundService $sounds,
    ): RedirectResponse {
        $this->authorize('manageTaskManagementSettings');

        $sounds->deleteCustomSound();

        return back()->with('success', 'Custom notification sound removed.');
    }
}
