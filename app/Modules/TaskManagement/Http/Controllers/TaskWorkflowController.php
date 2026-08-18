<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\TaskManagement\Enums\TaskStatus;
use App\Modules\TaskManagement\Exceptions\TaskWorkflowException;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Services\TaskWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Everything that changes who holds a task or what state it is in. Kept apart
 * from TaskController so that CRUD cannot quietly bypass the state machine.
 */
class TaskWorkflowController extends Controller
{
    public function __construct(protected TaskWorkflow $workflow) {}

    public function publish(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('assign', $task);

        return $this->run(fn () => $this->workflow->publishToBoard($task, $request->user()),
            'Task published to the open board.');
    }

    public function assign(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('assign', $task);

        $validated = $request->validate([
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')],
        ]);

        $employee = Employee::findOrFail($validated['employee_id']);

        return $this->run(fn () => $this->workflow->assign($task, $employee, $request->user()),
            'Task assigned. It is now waiting for them to accept.');
    }

    public function claim(Request $request, Task $task): RedirectResponse
    {
        $employee = $request->user()->employee;

        abort_if($employee === null, 403);

        $task->refresh();

        if (! $request->user()->can('claim', $task)) {
            if ($task->status === TaskStatus::InProgress) {
                return back()->with('error', TaskWorkflowException::alreadyClaimed()->getMessage());
            }

            abort(403);
        }

        try {
            $this->workflow->claim($task, $employee, $request->user());
        } catch (TaskWorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('tasks.show', $task)->with('success', 'Task claimed. You are now working on it.');
    }

    public function accept(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('respond', $task);

        $employee = $request->user()->employee;

        abort_if($employee === null, 403);

        return $this->run(fn () => $this->workflow->accept($task, $employee, $request->user()),
            'Task accepted. Work is now in progress.');
    }

    public function decline(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('respond', $task);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $employee = $request->user()->employee;

        abort_if($employee === null, 403);

        return $this->run(
            fn () => $this->workflow->decline($task, $employee, $request->user(), $validated['reason'] ?? null),
            'Task declined and returned to the open board.',
        );
    }

    public function status(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('progress', $task);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ]);

        $target = TaskStatus::from($validated['status']);

        return $this->run(fn () => $this->workflow->transition($task, $target, $request->user()),
            "Task moved to {$target->label()}.");
    }

    /**
     * A broken workflow rule is usually two people acting at once, so it comes
     * back as a message on the page rather than an error screen.
     */
    protected function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (TaskWorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }
}
