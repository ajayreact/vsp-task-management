<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Models\TimeEntry;
use App\Modules\TaskManagement\Services\TimeTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimeEntryController extends Controller
{
    public function __construct(protected TimeTracker $tracker) {}

    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('logTime', $task);

        $validated = $request->validate([
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after:started_at'],
            'note' => ['nullable', 'string', 'max:500'],
            'is_billable' => ['sometimes', 'boolean'],
        ]);

        $employee = $request->user()->employee;
        abort_if($employee === null, 403);

        try {
            $this->tracker->logManual($task, $employee, $validated);
        } catch (ProductivityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Time logged.');
    }

    public function destroy(TimeEntry $entry): RedirectResponse
    {
        $this->authorize('delete', $entry);

        try {
            $this->tracker->discard($entry);
        } catch (ProductivityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Time entry removed.');
    }
}
