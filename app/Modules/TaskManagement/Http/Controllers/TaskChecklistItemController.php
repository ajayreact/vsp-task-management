<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskChecklistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskChecklistItemController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('manageChecklist', $task);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
        ]);

        $nextOrder = (int) $task->checklistItems()->max('sort_order') + 1;

        $task->checklistItems()->create([
            'title' => trim($validated['title']),
            'sort_order' => $nextOrder,
        ]);

        return back()->with('success', 'Checklist item added.');
    }

    public function update(Request $request, Task $task, TaskChecklistItem $item): RedirectResponse
    {
        $this->ensureBelongsToTask($task, $item);
        $this->authorize('update', $item);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:500'],
        ]);

        $item->update(['title' => trim($validated['title'])]);

        return back()->with('success', 'Checklist item updated.');
    }

    public function toggle(Request $request, Task $task, TaskChecklistItem $item): RedirectResponse
    {
        $this->ensureBelongsToTask($task, $item);
        $this->authorize('toggle', $item);

        $completed = ! $item->is_completed;

        $item->update([
            'is_completed' => $completed,
            'completed_by_user_id' => $completed ? $request->user()->id : null,
            'completed_at' => $completed ? now() : null,
        ]);

        return back();
    }

    public function destroy(Task $task, TaskChecklistItem $item): RedirectResponse
    {
        $this->ensureBelongsToTask($task, $item);
        $this->authorize('delete', $item);

        $item->delete();

        return back()->with('success', 'Checklist item removed.');
    }

    public function reorder(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('manageChecklist', $task);

        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', Rule::exists('tm_task_checklist_items', 'id')->where('tm_task_id', $task->id)],
        ]);

        DB::transaction(function () use ($task, $validated) {
            foreach (array_values($validated['order']) as $position => $itemId) {
                $task->checklistItems()
                    ->whereKey($itemId)
                    ->update(['sort_order' => $position + 1]);
            }
        });

        return back();
    }

    protected function ensureBelongsToTask(Task $task, TaskChecklistItem $item): void
    {
        abort_unless((int) $item->tm_task_id === (int) $task->id, 404);
    }
}
