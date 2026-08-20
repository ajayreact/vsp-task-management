<?php

namespace App\Modules\Core\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Modules\Core\Http\Requests\Settings\NotificationPreferenceUpdateRequest;
use App\Modules\Core\Services\UserNotificationPreferenceService;
use Illuminate\Http\RedirectResponse;

class NotificationPreferenceController extends Controller
{
    public function __construct(protected UserNotificationPreferenceService $preferences) {}

    public function update(NotificationPreferenceUpdateRequest $request): RedirectResponse
    {
        $this->preferences->update($request->user(), $request->validated());

        return back()->with('success', 'Notification preferences saved.');
    }
}
