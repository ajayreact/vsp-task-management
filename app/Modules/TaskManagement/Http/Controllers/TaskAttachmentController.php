<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Http\Requests\TaskAttachmentRequest;
use App\Modules\TaskManagement\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TaskAttachmentController extends Controller
{
    public function store(TaskAttachmentRequest $request, Task $task): RedirectResponse
    {
        $this->authorize('attachFiles', $task);

        foreach ($request->file('files', []) as $file) {
            $task->addMedia($file)
                ->withCustomProperties([
                    'uploaded_by_user_id' => $request->user()->id,
                ])
                ->toMediaCollection('attachments');
        }

        return back()->with('success', 'Files attached.');
    }

    public function destroy(Request $request, Task $task, Media $media): RedirectResponse
    {
        abort_unless(
            $media->collection_name === 'attachments'
                && $media->model_type === $task->getMorphClass()
                && (int) $media->model_id === (int) $task->id,
            404,
        );

        $this->authorize('deleteAttachment', [$task, $media]);

        $media->delete();

        return back()->with('success', 'Attachment removed.');
    }
}
