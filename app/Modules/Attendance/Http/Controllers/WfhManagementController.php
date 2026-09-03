<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Enums\WfhRequestType;
use App\Modules\Attendance\Http\Requests\WfhAssignmentFormRequest;
use App\Modules\Attendance\Models\WfhRequest;
use App\Modules\Attendance\Services\WfhRequestService;
use App\Modules\Core\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Carbon;

class WfhManagementController extends Controller
{
    public function __construct(protected WfhRequestService $wfh) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WfhRequest::class);
        abort_unless($request->user()?->can('manageWfhRequests'), 403);

        $payload = $this->wfh->managementPayload([
            'status' => $request->string('status')->value(),
            'type' => $request->string('type')->value(),
            'employee_id' => $request->integer('employee_id') ?: null,
            'department_id' => $request->integer('department_id') ?: null,
            'date' => $request->string('date')->value(),
        ]);

        return Inertia::render('Attendance/wfh/manage', [
            ...$payload,
            'statuses' => WfhRequestStatus::options(),
            'types' => WfhRequestType::options(),
            'filterOptions' => $this->wfh->filterOptions(),
        ]);
    }

    public function assign(WfhAssignmentFormRequest $request): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));

        $startDate = Carbon::parse($request->validated('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->validated('end_date'))->startOfDay();
        $hadPendingConflict = $this->wfh->hasPendingConflicts($employee->id, $startDate, $endDate);

        $this->wfh->assignDirect(
            $employee,
            $request->user(),
            $startDate,
            $endDate,
            $request->validated('reason'),
            $request->validated('notes'),
        );

        $message = 'WFH assigned successfully.';
        if ($hadPendingConflict) {
            $message .= ' An existing pending WFH request for overlapping dates was rejected.';
        }

        return back()->with('success', $message);
    }

    public function update(WfhAssignmentFormRequest $request, WfhRequest $wfhRequest): RedirectResponse
    {
        $this->authorize('update', $wfhRequest);

        $employee = Employee::query()->findOrFail($request->validated('employee_id'));
        abort_unless($employee->id === $wfhRequest->employee_id, 422);

        $this->wfh->updateAssignment(
            $wfhRequest,
            $request->user(),
            Carbon::parse($request->validated('start_date'))->startOfDay(),
            Carbon::parse($request->validated('end_date'))->startOfDay(),
            $request->validated('reason'),
            $request->validated('notes'),
        );

        return back()->with('success', 'WFH assignment updated.');
    }

    public function approve(WfhRequest $wfhRequest): RedirectResponse
    {
        $this->authorize('approve', $wfhRequest);

        $this->wfh->approve($wfhRequest, request()->user());

        return back()->with('success', 'WFH request approved.');
    }

    public function reject(WfhRequest $wfhRequest): RedirectResponse
    {
        $this->authorize('reject', $wfhRequest);

        $this->wfh->reject($wfhRequest, request()->user());

        return back()->with('success', 'WFH request rejected.');
    }

    public function cancel(WfhRequest $wfhRequest): RedirectResponse
    {
        $this->authorize('cancel', $wfhRequest);

        $this->wfh->cancelAssignment($wfhRequest, request()->user());

        return back()->with('success', 'WFH assignment cancelled.');
    }
}
