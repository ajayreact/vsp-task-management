<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\User;
use App\Modules\TaskManagement\Enums\SubtaskStatus;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TaskSubtask;
use App\Modules\TaskManagement\Services\TaskNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskSubtaskController extends Controller
{
    public function __construct(protected TaskNotifier $notifier) {}

    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('manageSubtasks', $task);

        $validated = $this->validateSubtask($request);
        $status = SubtaskStatus::from($validated['status'] ?? SubtaskStatus::Pending->value);
        $nextOrder = (int) $task->subtasks()->max('sort_order') + 1;

        $subtask = $task->subtasks()->create([
            'title' => trim($validated['title']),
            'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
            'status' => $status,
            'assigned_employee_id' => $validated['assigned_employee_id'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'completed_at' => $status->isCompleted() ? now() : null,
            'sort_order' => $nextOrder,
        ]);

        if ($subtask->assigned_employee_id !== null) {
            $this->notifier->subtaskAssigned($task, $subtask, $request->user());
        }

        return back()->with('success', 'Subtask added.');
    }

    public function update(Request $request, Task $task, TaskSubtask $subtask): RedirectResponse
    {
        $this->ensureBelongsToTask($task, $subtask);
        $this->authorize('update', $subtask);

        $validated = $this->validateSubtask($request, true);
        $previousAssigneeId = $subtask->assigned_employee_id;
        $previousSnapshot = $this->notificationSnapshot($subtask);

        $status = SubtaskStatus::from($validated['status']);
        $subtask->update([
            'title' => trim($validated['title']),
            'description' => isset($validated['description']) ? trim((string) $validated['description']) : null,
            'status' => $status,
            'assigned_employee_id' => $validated['assigned_employee_id'] ?? null,
            'due_at' => $validated['due_at'] ?? null,
            'completed_at' => $status->isCompleted() ? ($subtask->completed_at ?? now()) : null,
        ]);

        $subtask->refresh();
        $this->dispatchSubtaskNotifications($task, $subtask, $request->user(), $previousAssigneeId, $previousSnapshot);

        return back()->with('success', 'Subtask updated.');
    }

    public function toggle(Request $request, Task $task, TaskSubtask $subtask): RedirectResponse
    {
        $this->ensureBelongsToTask($task, $subtask);
        $this->authorize('toggle', $subtask);

        $previousSnapshot = $this->notificationSnapshot($subtask);
        $completed = ! $subtask->status->isCompleted();

        $subtask->update([
            'status' => $completed ? SubtaskStatus::Completed : SubtaskStatus::Pending,
            'completed_at' => $completed ? now() : null,
        ]);

        $subtask->refresh();

        if ($subtask->assigned_employee_id !== null && $previousSnapshot !== $this->notificationSnapshot($subtask)) {
            $this->notifier->subtaskUpdated($task, $subtask, $request->user());
        }

        return back();
    }

    public function destroy(Task $task, TaskSubtask $subtask): RedirectResponse
    {
        $this->ensureBelongsToTask($task, $subtask);
        $this->authorize('delete', $subtask);

        $subtask->delete();

        return back()->with('success', 'Subtask removed.');
    }

    public function reorder(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('manageSubtasks', $task);

        $validated = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', Rule::exists('tm_task_subtasks', 'id')->where('tm_task_id', $task->id)],
        ]);

        DB::transaction(function () use ($task, $validated) {
            foreach (array_values($validated['order']) as $position => $subtaskId) {
                $task->subtasks()
                    ->whereKey($subtaskId)
                    ->update(['sort_order' => $position + 1]);
            }
        });

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateSubtask(Request $request, bool $requireStatus = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:5000'],
            'status' => [$requireStatus ? 'required' : 'nullable', Rule::enum(SubtaskStatus::class)],
            'assigned_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'due_at' => ['nullable', 'date'],
        ]);
    }

    /**
     * @return array{title: string, description: string|null, status: string, due_at: string|null}
     */
    protected function notificationSnapshot(TaskSubtask $subtask): array
    {
        return [
            'title' => $subtask->title,
            'description' => $subtask->description,
            'status' => $subtask->status->value,
            'due_at' => $subtask->due_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array{title: string, description: string|null, status: string, due_at: string|null}  $previousSnapshot
     */
    protected function dispatchSubtaskNotifications(
        Task $task,
        TaskSubtask $subtask,
        User $actor,
        ?int $previousAssigneeId,
        array $previousSnapshot,
    ): void {
        if ($subtask->assigned_employee_id === null) {
            return;
        }

        if ($subtask->assigned_employee_id !== $previousAssigneeId) {
            $this->notifier->subtaskAssigned($task, $subtask, $actor);

            return;
        }

        if ($previousSnapshot !== $this->notificationSnapshot($subtask)) {
            $this->notifier->subtaskUpdated($task, $subtask, $actor);
        }
    }

    protected function ensureBelongsToTask(Task $task, TaskSubtask $subtask): void
    {
        abort_unless((int) $subtask->tm_task_id === (int) $task->id, 404);
    }
}
