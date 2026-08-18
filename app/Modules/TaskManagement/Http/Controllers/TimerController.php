<?php

namespace App\Modules\TaskManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TaskManagement\Exceptions\ProductivityException;
use App\Modules\TaskManagement\Models\Task;
use App\Modules\TaskManagement\Services\TimeTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TimerController extends Controller
{
    public function __construct(protected TimeTracker $tracker) {}

    public function start(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('logTime', $task);
        $employee = $request->user()->employee;
        abort_if($employee === null, 403);

        return $this->run(
            fn () => $this->tracker->start($task, $employee, $request->user()),
            'Timer started.',
        );
    }

    public function pause(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('logTime', $task);
        $employee = $request->user()->employee;
        abort_if($employee === null, 403);

        return $this->run(
            fn () => $this->tracker->pause($task, $employee),
            'Timer paused.',
        );
    }

    public function stop(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('logTime', $task);
        $employee = $request->user()->employee;
        abort_if($employee === null, 403);

        return $this->run(
            fn () => $this->tracker->stop($task, $employee),
            'Timer stopped.',
        );
    }

    protected function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (ProductivityException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }
}
