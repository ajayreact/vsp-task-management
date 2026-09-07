<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Http\Requests\WfhRequestFormRequest;
use App\Modules\Attendance\Http\Requests\WfhSelfRecordFormRequest;
use App\Modules\Attendance\Models\WfhRequest;
use App\Modules\Attendance\Services\WfhRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class WfhRequestController extends Controller
{
    public function __construct(protected WfhRequestService $wfh) {}

    public function index(): Response
    {
        $this->authorize('viewAny', WfhRequest::class);

        $user = request()->user();
        $employee = $user?->employee;
        abort_if($employee === null, 403);

        $directMode = (bool) $user?->isSuperAdmin();

        return Inertia::render('Attendance/wfh/index', [
            'mode' => $directMode ? 'direct' : 'request',
            'requests' => $this->wfh
                ->forEmployee($employee)
                ->map(fn (WfhRequest $request) => $this->wfh->serialize($request, $directMode))
                ->values(),
            'statuses' => WfhRequestStatus::options(),
        ]);
    }

    public function store(WfhRequestFormRequest $request): RedirectResponse
    {
        $user = $request->user();
        $employee = $user?->employee;
        abort_if($employee === null, 403);

        $startDate = Carbon::parse($request->validated('start_date'))->startOfDay();
        $endDate = Carbon::parse($request->validated('end_date'))->startOfDay();
        $reason = $request->validated('reason');

        if ($user->isSuperAdmin()) {
            $this->wfh->recordOwnWfh($employee, $user, $startDate, $endDate, $reason);

            return back()->with('success', 'WFH recorded.');
        }

        $this->wfh->createRequest($employee, $user, $startDate, $endDate, $reason);

        return back()->with('success', 'WFH request submitted.');
    }

    public function update(WfhSelfRecordFormRequest $request, WfhRequest $wfhRequest): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user?->isSuperAdmin(), 403);
        abort_unless($user->employee !== null && $wfhRequest->employee_id === $user->employee->id, 403);

        $this->authorize('update', $wfhRequest);

        $this->wfh->updateAssignment(
            $wfhRequest,
            $user,
            Carbon::parse($request->validated('start_date'))->startOfDay(),
            Carbon::parse($request->validated('end_date'))->startOfDay(),
            $request->validated('reason'),
        );

        return back()->with('success', 'WFH record updated.');
    }

    public function destroy(WfhRequest $wfhRequest): RedirectResponse
    {
        $user = request()->user();
        abort_unless($user?->isSuperAdmin(), 403);
        abort_unless($user->employee !== null && $wfhRequest->employee_id === $user->employee->id, 403);

        $this->authorize('cancel', $wfhRequest);

        $this->wfh->cancelAssignment($wfhRequest, $user);

        return back()->with('success', 'WFH record removed.');
    }
}
