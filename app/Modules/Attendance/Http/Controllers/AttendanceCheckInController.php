<?php

namespace App\Modules\Attendance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Attendance\Exceptions\AttendanceWorkflowException;
use App\Modules\Attendance\Http\Requests\AttendanceCoordinatesRequest;
use App\Modules\Attendance\Services\AttendanceCheckInOutService;
use Illuminate\Http\RedirectResponse;

class AttendanceCheckInController extends Controller
{
    public function __construct(protected AttendanceCheckInOutService $attendance) {}

    public function __invoke(AttendanceCoordinatesRequest $request): RedirectResponse
    {
        $employee = $request->user()?->employee;

        abort_if($employee === null, 403);

        try {
            $this->attendance->checkIn(
                $employee,
                (float) $request->validated('latitude'),
                (float) $request->validated('longitude'),
                $request->ip(),
            );
        } catch (AttendanceWorkflowException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Checked in successfully.');
    }
}
