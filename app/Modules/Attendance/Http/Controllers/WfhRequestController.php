<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Enums\WfhRequestStatus;
use App\Modules\Attendance\Http\Requests\WfhRequestFormRequest;
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

        $employee = request()->user()?->employee;
        abort_if($employee === null, 403);

        return Inertia::render('Attendance/wfh/index', [
            'requests' => $this->wfh->forEmployee($employee)->map(fn (WfhRequest $request) => $this->wfh->serialize($request))->values(),
            'statuses' => WfhRequestStatus::options(),
        ]);
    }

    public function store(WfhRequestFormRequest $request): RedirectResponse
    {
        $employee = $request->user()?->employee;
        abort_if($employee === null, 403);

        $this->wfh->create(
            $employee,
            Carbon::parse($request->validated('date'))->startOfDay(),
            $request->validated('reason'),
        );

        return back()->with('success', 'WFH request submitted.');
    }
}
