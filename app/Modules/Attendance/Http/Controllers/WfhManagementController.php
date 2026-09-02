<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Models\WfhRequest;
use App\Modules\Attendance\Services\WfhRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WfhManagementController extends Controller
{
    public function __construct(protected WfhRequestService $wfh) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', WfhRequest::class);
        abort_unless($request->user()?->can('manageWfhRequests'), 403);

        $payload = $this->wfh->managementPayload([
            'status' => $request->string('status')->value(),
            'employee_id' => $request->integer('employee_id') ?: null,
            'department_id' => $request->integer('department_id') ?: null,
            'date' => $request->string('date')->value(),
        ]);

        return Inertia::render('Attendance/wfh/manage', [
            ...$payload,
            'statuses' => WfhRequestStatus::options(),
            'filterOptions' => $this->wfh->filterOptions(),
        ]);
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
}
