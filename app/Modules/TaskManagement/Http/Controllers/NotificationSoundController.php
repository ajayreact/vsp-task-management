<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Enums\Ability;
use App\Modules\TaskManagement\Services\TaskManagementNotificationSoundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NotificationSoundController extends Controller
{
    public function custom(Request $request, TaskManagementNotificationSoundService $sounds): StreamedResponse
    {
        $user = $request->user();

        abort_unless(
            $user !== null && $user->isInternal() && $user->can(Ability::AccessTasks->value),
            403,
        );

        $media = $sounds->customMedia();
        abort_if($media === null, 404);

        return Storage::disk($media->disk)->response(
            $media->getPathRelativeToRoot(),
            $media->file_name,
            ['Content-Type' => $media->mime_type],
        );
    }
}
