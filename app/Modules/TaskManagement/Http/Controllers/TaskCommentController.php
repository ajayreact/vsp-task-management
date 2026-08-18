<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskComment;
use App\Modules\TaskManagement\Services\TaskNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskCommentController extends Controller
{
    public function __construct(protected TaskNotifier $notifier) {}

    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('comment', $task);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment = $task->comments()->create([
            'user_id' => $request->user()->id,
            'body' => trim($validated['body']),
        ]);

        $this->notifier->taskCommented($task, $request->user());

        return back()->with('success', 'Comment added.');
    }

    public function update(Request $request, Task $task, TaskComment $comment): RedirectResponse
    {
        $this->ensureBelongsToTask($task, $comment);
        $this->authorize('update', $comment);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $comment->update(['body' => trim($validated['body'])]);

        return back()->with('success', 'Comment updated.');
    }

    public function destroy(Task $task, TaskComment $comment): RedirectResponse
    {
        $this->ensureBelongsToTask($task, $comment);
        $this->authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', 'Comment removed.');
    }

    protected function ensureBelongsToTask(Task $task, TaskComment $comment): void
    {
        abort_unless((int) $comment->tm_task_id === (int) $task->id, 404);
    }
}
