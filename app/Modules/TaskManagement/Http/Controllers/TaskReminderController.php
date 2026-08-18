<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskReminderController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('manageReminders', $task);

        $validated = $request->validate([
            'remind_at' => ['required', 'date', 'after:now'],
            'recipient_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $recipient = User::query()->findOrFail($validated['recipient_user_id']);
        abort_unless($recipient->isInternal() && $recipient->is_active, 422);

        $task->reminders()->create([
            'recipient_user_id' => $recipient->id,
            'created_by_user_id' => $request->user()->id,
            'remind_at' => $validated['remind_at'],
            'message' => isset($validated['message']) ? trim((string) $validated['message']) : null,
        ]);

        return back()->with('success', 'Reminder scheduled.');
    }

    public function destroy(Task $task, TaskReminder $reminder): RedirectResponse
    {
        abort_unless((int) $reminder->tm_task_id === (int) $task->id, 404);
        $this->authorize('delete', $reminder);

        $reminder->delete();

        return back()->with('success', 'Reminder cancelled.');
    }
}
