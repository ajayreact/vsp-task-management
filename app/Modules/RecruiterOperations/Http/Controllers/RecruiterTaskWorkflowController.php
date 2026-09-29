<?php

namespace App\Modules\RecruiterOperations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Core\Models\Employee;
use App\Modules\RecruiterOperations\Exceptions\RecruiterTaskWorkflowException;
use App\Modules\RecruiterOperations\Http\Requests\CompleteRecruiterTaskRequest;
use App\Modules\RecruiterOperations\Http\Requests\ReassignRecruiterTaskRequest;
use App\Modules\RecruiterOperations\Http\Requests\RecruiterTaskReasonRequest;
use App\Modules\RecruiterOperations\Models\RecruiterTask;
use App\Modules\RecruiterOperations\Services\RecruiterTaskWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Everything that changes who holds a recruiter task or what state it is in.
 * Kept apart from RecruiterTaskController so CRUD cannot bypass the workflow.
 */
class RecruiterTaskWorkflowController extends Controller
{
    public function __construct(protected RecruiterTaskWorkflow $workflow) {}

    public function accept(Request $request, RecruiterTask $recruiterTask): RedirectResponse
    {
        $this->authorize('accept', $recruiterTask);

        return $this->run(fn () => $this->workflow->accept($recruiterTask, $request->user()),
            'Task accepted. It is now in progress.');
    }

    public function decline(RecruiterTaskReasonRequest $request, RecruiterTask $recruiterTask): RedirectResponse
    {
        $this->authorize('decline', $recruiterTask);

        return $this->run(fn () => $this->workflow->decline($recruiterTask, $request->user(), $request->validated('reason')),
            'Task declined. Your recruiter lead has been told.');
    }

    public function hold(RecruiterTaskReasonRequest $request, RecruiterTask $recruiterTask): RedirectResponse
    {
        $this->authorize('hold', $recruiterTask);

        return $this->run(fn () => $this->workflow->hold($recruiterTask, $request->user(), $request->validated('reason')),
            'Task put on hold.');
    }

    public function resume(Request $request, RecruiterTask $recruiterTask): RedirectResponse
    {
        $this->authorize('resume', $recruiterTask);

        return $this->run(fn () => $this->workflow->resume($recruiterTask, $request->user()),
            'Task resumed.');
    }

    public function complete(CompleteRecruiterTaskRequest $request, RecruiterTask $recruiterTask): RedirectResponse
    {
        $this->authorize('complete', $recruiterTask);

        $achieved = $request->validated('achieved_count');

        return $this->run(fn () => $this->workflow->complete(
            $recruiterTask,
            $request->user(),
            $achieved === null ? null : (int) $achieved,
            $request->validated('completion_note'),
        ), 'Task completed.');
    }

    public function reopen(RecruiterTaskReasonRequest $request, RecruiterTask $recruiterTask): RedirectResponse
    {
        $this->authorize('reopen', $recruiterTask);

        return $this->run(fn () => $this->workflow->reopen($recruiterTask, $request->user(), $request->validated('reason')),
            'Task reopened and back in progress.');
    }

    public function cancel(RecruiterTaskReasonRequest $request, RecruiterTask $recruiterTask): RedirectResponse
    {
        $this->authorize('cancel', $recruiterTask);

        return $this->run(fn () => $this->workflow->cancel($recruiterTask, $request->user(), $request->validated('reason')),
            'Task cancelled.');
    }

    public function reassign(ReassignRecruiterTaskRequest $request, RecruiterTask $recruiterTask): RedirectResponse
    {
        $this->authorize('reassign', $recruiterTask);

        $recruiter = Employee::query()->findOrFail($request->validated('assigned_employee_id'));

        return $this->run(fn () => $this->workflow->reassign($recruiterTask, $recruiter, $request->user(), $request->validated('reason')),
            'Task reassigned. It is waiting for the recruiter to accept.');
    }

    /**
     * A broken workflow rule is usually two people acting at once, so it comes
     * back as a message on the page rather than an error screen.
     */
    protected function run(callable $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (RecruiterTaskWorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $success);
    }
}
